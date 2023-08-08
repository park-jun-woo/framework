<?php
namespace model;

use util\Debug;

class Papago{
	protected static Papago $instance;
	
	public static function init(string $cachePath, string $client_id,string $client_secret){
		self::$instance = new Papago($cachePath, $client_id, $client_secret);
	}
	public static function detect(string $text){
		return self::$instance->detectLocale($text);
	}
	public static function translate(string $text, string $source, string $target){
		return self::$instance->translateTo($text, $source, $target);
	}
	
	protected array $cache;
	protected string $client_id;
	protected string $client_secret;
	protected string $api_url;
	protected $path;
	
	protected function __construct(string $cachePath, string $client_id,string $client_secret) {
		$this->cache = array();
		$this->path = $cachePath."papago".DIRECTORY_SEPARATOR;
		$this->client_id = $client_id;
		$this->client_secret = $client_secret;
	}
	
	protected function detectLocale(string $text){
		if(!array_key_exists("detect",$this->cache)){
			$this->cache["detect"] = array();
			$path = "{$this->path}detect";
			if(is_file($path)){
				$fp = fopen($path,"r");
				$loaded = fread($fp,filesize($path));
				fclose($fp);
				$rows = explode("\n",$loaded);
				foreach($rows as $row){
					if($row!=""){
						list($ko,$en) = explode(":",$row);
						$this->cache["{$source}{$target}"][$ko] = $en;
					}
				}
			}
		}
		if(!array_key_exists($text,$this->cache["detect"])){
			Debug::trace("Papago Detect API Call(\"{$text}\")","call");
			// 요청 헤더 설정
			$headers = array(
					"Content-Type: application/x-www-form-urlencoded; charset=UTF-8",
					"X-Naver-Client-Id: " . $this->client_id,
					"X-Naver-Client-Secret: " . $this->client_secret
			);
			// 요청 파라미터 설정
			$params = array("query" => $text);
			// cURL 초기화
			$curl = curl_init();
			// cURL 옵션 설정
			curl_setopt($curl,CURLOPT_URL,"https://openapi.naver.com/v1/papago/detectLangs");
			curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
			curl_setopt($curl,CURLOPT_POST,true);
			curl_setopt($curl,CURLOPT_POSTFIELDS,http_build_query($params));
			curl_setopt($curl,CURLOPT_HTTPHEADER,$headers);
			// API 호출
			$response = curl_exec($curl);
			// cURL 세션 종료
			curl_close($curl);
			// 응답 JSON 파싱
			$result = json_decode($response,true);
			$this->cache["detect"][$text] = $result["langCode"];
			//캐시에 저장
			$path = "{$this->path}detect";
			$fp = fopen($path,"a");
			fwrite($fp,"{$text}:{$result["langCode"]}".PHP_EOL);
			fclose($fp);
		}
		//감지 결과 반환
		return $this->cache["detect"][$text];
	}
	
	protected function translateTo(string $text, string $source, string $target) {
		if(!array_key_exists("{$source}{$target}",$this->cache)){
			$this->cache["{$source}{$target}"] = array();
			$path = "{$this->path}{$source}{$target}";
			if(is_file($path)){
				$fp = fopen($path,"r");
				$loaded = fread($fp,filesize($path));
				fclose($fp);
				$rows = explode("\n",$loaded);
				foreach($rows as $row){
					if($row!=""){
						list($ko,$en) = explode(":",$row);
						$this->cache["{$source}{$target}"][$ko] = $en;
					}
				}
			}
		}
		if(!array_key_exists($text,$this->cache["{$source}{$target}"])){
			Debug::trace("Papago Translate API Call(\"{$text}\")","call");
			// 요청 헤더 설정
			$headers = array(
				"Content-Type: application/x-www-form-urlencoded; charset=UTF-8",
				"X-Naver-Client-Id: " . $this->client_id,
				"X-Naver-Client-Secret: " . $this->client_secret
			);
			// 요청 파라미터 설정
			$params = array("source" => $source,"target" => $target,"text" => $text);
			// cURL 초기화
			$curl = curl_init();
			// cURL 옵션 설정
			curl_setopt($curl,CURLOPT_URL,"https://openapi.naver.com/v1/papago/n2mt");
			curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
			curl_setopt($curl,CURLOPT_POST,true);
			curl_setopt($curl,CURLOPT_POSTFIELDS,http_build_query($params));
			curl_setopt($curl,CURLOPT_HTTPHEADER,$headers);
			// API 호출
			$response = curl_exec($curl);
			// cURL 세션 종료
			curl_close($curl);
			// 응답 JSON 파싱
			$result = json_decode($response,true);
			$this->cache["{$source}{$target}"][$text] = $result["message"]["result"]["translatedText"];
			//캐시에 저장
			$path = "{$this->path}{$source}{$target}";
			$fp = fopen($path,"a");
			fwrite($fp,"{$text}:{$result["message"]["result"]["translatedText"]}".PHP_EOL);
			fclose($fp);
		}
		//번역 결과 반환
		return $this->cache["{$source}{$target}"][$text];
	}
}