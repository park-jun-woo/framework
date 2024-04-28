<?php
namespace Parkjunwoo\Util;

use Parkjunwoo\Parkjunwoo;

class Memory{
    protected string $serverKey;
    protected array $semaphores;
    
    public function __construct(int $serverKey){
        $this->serverKey = (string)$serverKey;
        $this->semaphores = array();
    }
    
    public function __destruct(){
        foreach($this->semaphores as $semaphore){sem_remove($semaphore);}
    }
    
    public function get(string $key){
        return apcu_fetch($this->serverKey.$key);
    }
    
    public function set(string $key, $value):int{
        apcu_store($this->serverKey.$key, $value);
    }
    
    public function delete(string $key):bool{
        return apcu_delete($this->serverKey.$key);
    }
    
    public function lock(string $key):bool{
        $semaphoreKey = $this->serverKey.$key;
        if(array_key_exists($semaphoreKey, $this->semaphores)){$semaphore = $this->semaphores[$semaphoreKey];}
        else{$semaphore = $this->semaphores[$semaphoreKey] = sem_get(ftok($semaphoreKey));}
        sem_acquire($semaphore);
    }
    
    public function unlock(string $key):bool{
        $semaphoreKey = $this->serverKey.$key;
        if(array_key_exists($semaphoreKey, $this->semaphores)){$semaphore = $this->semaphores[$semaphoreKey];}
        else{$semaphore = $this->semaphores[$semaphoreKey] = sem_get(ftok($semaphoreKey));}
        sem_release($semaphore);
    }
}