<?php
namespace util;

use Parkjunwoo;

class Memory{
	protected string $serverKey;
	protected array $countSemaphores;
	protected array $lockSemaphores;
	
	public function __construct(int $serverKey){
		$this->serverKey = (string)$serverKey;
		$this->countSemaphores = array();
		$this->lockSemaphores = array();
	}
	
	public function get(string $key){
		return apcu_fetch($key);
	}
	
	public function set(string $key, $value):int{
		
		apcu_store($key, $value);
	}
	
	public function delete(string $key):bool{
		return apcu_delete($key);
	}
	
	public function lock(string $key, int $operation, int &$wouldblock=null):bool{
		$countKey = "{$this->serverKey}c{$key}";
		$lockKey = "{$this->serverKey}l{$key}";
		if(array_key_exists($countKey, $this->countSemaphores)){$countSemaphore = $this->countSemaphores[$countKey];}
		else{$countSemaphore = $this->countSemaphores[$countKey] = sem_get($countKey);}
		if(array_key_exists($lockKey, $this->lockSemaphores)){$lockSemaphore = $this->lockSemaphores[$lockKey];}
		else{$lockSemaphore = $this->lockSemaphores[$lockKey] = sem_get($lockKey);}
		
		switch($operation){
			case LOCK_SH:
				//카운터 락 획득
				if(sem_acquire($countSemaphore)){
					//메모리에 주어진 키에 대해 카운터가 존재하지 않거나 0이라면
					if(!apcu_exists($countKey) || ($count = apcu_fetch($countKey))==0){
						apcu_store($countKey, 1);
						//락 획득
						sem_acquire($lockSemaphore);
						//카운터 락 해제
						sem_release($countSemaphore);
					}
					//카운터가 -1이라면 배제락 상태이므로 카운터락을 해제하고 배제락이 풀릴 때까지 대기한다.
					else if($count==-1){
						$acquired = sem_acquire($lockSemaphore, true);
						//카운터 락 해제
						sem_release($countSemaphore);
						//락 획득할 때까지 대기한다.
						if(!$acquired){sem_acquire($lockSemaphore);}
						//락을 획득하면 다시 카운터 락을 걸고 카운터에 1저장하고 카운터 락 해제한다.
						if(sem_acquire($countSemaphore)){
							apcu_store($countKey, 1);
							//카운터 락 해제
							sem_release($countSemaphore);
						}
					}
					//카운터가 0도 아니고 -1도 아니라면 공유락 상태이므로 카운트를 1 올리고 그냥 진행한다.
					else{
						apcu_inc($countKey,1);
						//카운터 락 해제
						sem_release($countSemaphore);
					}
				}
				break;
			case LOCK_EX:
				//카운터 락 획득
				if(sem_acquire($countSemaphore)){
					//카운터가 존재하지 않거나 0이라면
					if(!apcu_exists($countKey) || ($count = apcu_fetch($countKey))==0){
						apcu_store($countKey, -1);
						//락 획득
						sem_acquire($lockSemaphore);
						//카운터 락 해제
						sem_release($countSemaphore);
					}
					//카운터가 0이 아니라면 공유락 또는 배제락 상태이므로 락이 풀릴 때까지 대기한다.
					else{
						$acquired = sem_acquire($lockSemaphore, true);
						//카운터 락 해제
						sem_release($countSemaphore);
						//락 획득할 때까지 대기한다.
						if(!$acquired){sem_acquire($lockSemaphore);}
						//락을 획득하면 다시 카운터 락을 걸고 카운터에 -1저장하고 카운터 락 해제한다.
						if(sem_acquire($countSemaphore)){
							apcu_store($countKey, -1);
							//카운터 락 해제
							sem_release($countSemaphore);
						}
					}
				}
				break;
			case LOCK_UN:
				//카운터 락 획득
				if(sem_acquire($countSemaphore)){
					
				}
				break;
			case LOCK_SH|LOCK_NB:
				break;
			case LOCK_EX|LOCK_NB:
				break;
		}
	}
	
	public static function keyToInt(string $key, int $system=0):int{
		if(($length = strlen($key))>12){return -1;}
		if($system<1 || $system>15){return -1;}
		$int = 0;
		for($iu=0;$iu<$length;$iu++){
			$char = ord($key[$iu]);
			if($char>41 && $char<48){$char -= 16;}
			else if($char>96 && $char<123){$char -= 97;}
			else{return -1;}
			$int <<= 5;
			$int |= $char;
		}
		$int |= $system<<60;
		return $int;
	}
}