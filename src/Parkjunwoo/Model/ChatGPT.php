<?php
namespace Parkjunwoo\Model;

use Parkjunwoo\Util\Debug;

class ChatGPT{
    const MODEL_GPT35_TURBO = 0;
    const MODEL_GPT35_TURBO_16K = 1;
    const MODEL_GPT4 = 2;
    const MODEL_GPT4_32K = 3;
    protected static $modelMap = array(
        self::MODEL_GPT35_TURBO=>"gpt-3.5-turbo-0613",
        self::MODEL_GPT35_TURBO_16K=>"gpt-3.5-turbo-16k-0613",
        self::MODEL_GPT4=>"gpt-4-0613",
        self::MODEL_GPT4_32K=>"gpt-4-32k-0613"
    );
    protected static ChatGPT $instance;
    public static function init(string $cachePath, string $api_key){
        self::$instance = new ChatGPT($cachePath, $api_key);
    }
    public static function chat(int $model, $system, string $user, array $examples=array()){
        return self::$instance->gptChat($model, $system, $user, $examples);
    }
    public static function chat35($system, string $user, array $examples=array()){
        return self::$instance->gptChat(self::MODEL_GPT35_TURBO, $system, $user, $examples);
    }
    public static function chat35_16k($system, string $user, array $examples=array()){
        return self::$instance->gptChat(self::MODEL_GPT35_TURBO_16K, $system, $user, $examples);
    }
    public static function chat4($system, string $user, array $examples=array()){
        return self::$instance->gptChat(self::MODEL_GPT4, $system, $user, $examples);
    }
    public static function chat4_32k($system, string $user, array $examples=array()){
        return self::$instance->gptChat(self::MODEL_GPT4_32K, $system, $user, $examples);
    }
    
    protected string $path,$api_key;
    protected array $cache,$systems;
    
    protected function __construct(string $cachePath, string $api_key){
        $this->path = $cachePath."chatgpt".DIRECTORY_SEPARATOR;
        $this->api_key = $api_key;
        $this->cache = array();
        $this->systems = array();
        if(is_file("{$this->path}systems")){
            $handle = fopen("{$this->path}systems","r");
            while(($line=fgets($handle)) !== false){array_push($this->systems,str_replace("\n","",$line));}
            fclose($handle);
        }
    }
    
    protected function system(string $systemMessage){
        $systemMessage = str_replace("\r\n", " ", $systemMessage);
        $system = array_search($systemMessage,$this->systems);
        if($system===false){
            $handle = fopen("{$this->path}systems","a");
            fwrite($handle,"{$systemMessage}\n");
            fclose($handle);
            array_push($this->systems,$systemMessage);
            $system = count($this->systems)-1;
        }
        return $system;
    }
    
    protected function loadCache($systemMessage){
        if(is_string($systemMessage)){
            $system = $this->system($systemMessage);
        }else if(is_int($systemMessage)){
            $system = $systemMessage;
            $systemMessage = $this->systems[$system];
        }
        if(!array_key_exists($system,$this->cache)){
            $this->cache[$system] = array();
            if(is_file("{$this->path}{$system}")){
                $handle = fopen("{$this->path}{$system}","r");
                while(($line=fgets($handle)) !== false){
                    list($key,$value) = explode(":;",str_replace("\n","",$line));
                    $this->cache[$system][$key] = $value;
                }
                fclose($handle);
            }
        }
        return $system;
    }
    
    protected function cache(int $system,string $user,string $result){
        $this->cache[$system][$user] = $result;
        $handle = fopen("{$this->path}{$system}","a");
        fwrite($handle,"{$user}:;{$result}\n");
        fclose($handle);
    }
    
    protected function gptChat(int $model,$systemMessage,string $user,array $examples=array()){
        Debug::trace("ChatGPT::chat(".self::$modelMap[$model].",{$systemMessage},{$user})","compile");
        $system = $this->loadCache($systemMessage);
        if(is_int($systemMessage)){$systemMessage = $this->systems[$system];}
        if(array_key_exists($user,$this->cache[$system])){
            return $this->cache[$system][$user];
        }
        //요청 헤더 설정
        $headers = array(
            "Content-type: application/json",
            "Authorization: Bearer {$this->api_key}"
        );
        //메세지 데이터 구성
        $messages = array();
        array_push($messages,array("role"=>"system","content"=>$systemMessage));
        foreach($examples as $example){array_push($messages,$example);}
        array_push($messages,array("role"=>"user","content"=>$user));
        //cURL 초기화
        $curl = curl_init();
        //cURL 옵션 설정
        curl_setopt($curl,CURLOPT_URL,"https://api.openai.com/v1/chat/completions");
        curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($curl,CURLOPT_POST,true);
        curl_setopt($curl,CURLOPT_POSTFIELDS,json_encode(array("model"=>self::$modelMap[$model],"messages"=>$messages)));
        curl_setopt($curl,CURLOPT_HTTPHEADER,$headers);
        //API 호출
        $response = curl_exec($curl);
        if(curl_errno($curl)){
            
            //cURL 세션 종료
            curl_close($curl);
        }else{
            //응답 JSON 파싱
            $result = json_decode($response,true);
            $this->cache($system,$user,$result["choices"][0]["message"]["content"]);
            //cURL 세션 종료
            curl_close($curl);
            return $result["choices"][0]["message"]["content"];
        }
    }
}
?>