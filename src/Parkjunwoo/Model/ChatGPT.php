<?php
namespace Parkjunwoo\Model;

use Parkjunwoo\Util\Debug;
use Parkjunwoo\Interface\Singleton;
use Exception;

class ChatGPT implements Singleton{
    const MODEL_GPT35_TURBO = 0;
    const MODEL_GPT35_TURBO_16K = 1;
    const MODEL_GPT4 = 2;
    const MODEL_GPT4_32K = 3;

    protected static array $modelMap = [
        self::MODEL_GPT35_TURBO => "gpt-3.5-turbo-0613",
        self::MODEL_GPT35_TURBO_16K => "gpt-3.5-turbo-16k-0613",
        self::MODEL_GPT4 => "gpt-4-0613",
        self::MODEL_GPT4_32K => "gpt-4-32k-0613"
    ];

    private static ?ChatGPT $instance = null;
    protected string $path;
    protected string $api_key;
    protected array $cache = [];
    protected array $systems = [];

    /**
     * 인스턴스를 반환합니다.
     *
     * @param mixed ...$params 인스턴스 생성에 필요한 매개변수
     * @return self 인스턴스
     */
    public static function getInstance(...$params): self{
        if (self::$instance === null) {
            self::$instance = new self(...$params);
        }

        return self::$instance;
    }

    /**
     * ChatGPT 클래스의 생성자.
     * 
     * @param string $cachePath 캐시 파일의 경로
     * @param string $api_key API 키
     */
    protected function __construct(string $cachePath, string $api_key){
        $this->path = $cachePath . "chatgpt" . DIRECTORY_SEPARATOR;
        $this->api_key = $api_key;
        $this->loadSystems();
    }

    /**
     * 시스템 메시지를 로드합니다.
     *
     * @return void
     */
    protected function loadSystems(): void{
        if (is_file("{$this->path}systems")) {
            $lines = file("{$this->path}systems", FILE_IGNORE_NEW_LINES);
            if ($lines !== false) {
                $this->systems = $lines;
            }
        }
    }

    /**
     * 시스템 메시지를 저장하거나 로드합니다.
     *
     * @param string $systemMessage 시스템 메시지
     * @return int 저장된 시스템 메시지의 인덱스
     */
    protected function system(string $systemMessage): int{
        $systemMessage = str_replace("\r\n", " ", $systemMessage);
        $system = array_search($systemMessage, $this->systems);

        if ($system === false) {
            file_put_contents("{$this->path}systems", "{$systemMessage}\n", FILE_APPEND);
            $this->systems[] = $systemMessage;
            $system = count($this->systems) - 1;
        }

        return $system;
    }

    /**
     * 캐시된 시스템 메시지를 로드합니다.
     *
     * @param mixed $systemMessage 문자열 또는 인덱스 값
     * @return int 시스템 메시지의 인덱스
     */
    protected function loadCache($systemMessage): int{
        $system = is_string($systemMessage) ? $this->system($systemMessage) : $systemMessage;

        if (!isset($this->cache[$system])) {
            $this->cache[$system] = [];
            if (is_file("{$this->path}{$system}")) {
                $lines = file("{$this->path}{$system}", FILE_IGNORE_NEW_LINES);
                foreach ($lines as $line) {
                    [$key, $value] = explode(":;", $line);
                    $this->cache[$system][$key] = $value;
                }
            }
        }

        return $system;
    }

    /**
     * 캐시에 시스템 메시지와 결과를 저장합니다.
     *
     * @param int $system 시스템 메시지의 인덱스
     * @param string $user 사용자 메시지
     * @param string $result API 응답 결과
     * @return void
     */
    protected function cache(int $system, string $user, string $result): void{
        $this->cache[$system][$user] = $result;
        file_put_contents("{$this->path}{$system}", "{$user}:;{$result}\n", FILE_APPEND);
    }

    /**
     * OpenAI API를 사용하여 ChatGPT와 대화를 수행합니다.
     *
     * @param int $model 사용할 모델의 인덱스
     * @param mixed $systemMessage 시스템 메시지
     * @param string $user 사용자 메시지
     * @param array $examples 예시 메시지
     * @return string API로부터의 응답 메시지
     * @throws Exception
     */
    public function gptChat(int $model, $systemMessage, string $user, array $examples = []): string{
        Debug::trace("ChatGPT::chat(" . self::$modelMap[$model] . ", {$systemMessage}, {$user})", "compile");

        $system = $this->loadCache($systemMessage);
        $systemMessage = is_int($systemMessage) ? $this->systems[$system] : $systemMessage;

        if (isset($this->cache[$system][$user])) {
            return $this->cache[$system][$user];
        }

        $headers = [
            "Content-type: application/json",
            "Authorization: Bearer {$this->api_key}"
        ];

        $messages = array_merge(
            [["role" => "system", "content" => $systemMessage]],
            $examples,
            [["role" => "user", "content" => $user]]
        );

        $curl = curl_init("https://api.openai.com/v1/chat/completions");
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode(["model" => self::$modelMap[$model], "messages" => $messages]),
            CURLOPT_HTTPHEADER => $headers
        ]);

        $response = curl_exec($curl);

        if (curl_errno($curl)) {
            $error_message = curl_error($curl);
            curl_close($curl);
            throw new Exception("cURL Error: $error_message");
        }

        $result = json_decode($response, true);
        curl_close($curl);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("JSON Decode Error: " . json_last_error_msg());
        }

        $messageContent = $result["choices"][0]["message"]["content"] ?? null;

        if ($messageContent === null) {
            throw new Exception("Unexpected API response format.");
        }

        $this->cache($system, $user, $messageContent);

        return $messageContent;
    }
}
?>