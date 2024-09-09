<?php
namespace Parkjunwoo\Util;

use DOMDocument;
use DOMXPath;

class Security{
    /**
     * RSA 키쌍이 없을 경우 생성.
     * @param string $path 키를 생성할 경로
     * @param bool $clear 강제로 초기화할지 여부
     */
    public static function generateRSA():array{
        $privateKey = null;
        $privateKeyResource = openssl_pkey_new(["digest_alg" => "sha512","private_key_bits"=>2048,"private_key_type"=>OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($privateKeyResource, $privateKey);
        $publicKey = openssl_pkey_get_details($privateKeyResource)['key'];
        return [$privateKey, $publicKey];
    }
    /**
     * SQL 인젝션 공격 필터링
     * @param array $param 필터링할 매개 변수 값의 배열
     */
    public static function sqlInjectionClean(array &$param){
        foreach($param as &$value){
            if(is_array($value)){sqlInjectionClean($value);}
            else{$value = addSlashes($value);}
        }
    }
    public static function valid(string $value, string $type="key"):bool{
        switch($type){
            case "key":case "unsigned int":$regex = "/^\d+$/";break;
            case "int":$regex = "/^-?\d+$/";break;
            case "name":$regex = "/^[0-9a-zA-Z가-힣]+$/";break;
            default:return false;
        }
        return preg_match($regex, $value);
    }
    /**
     * XSS 공격 필터링
     * @param string $html 필터링할 입력 값
     * @param array $allowed 필터링하거나 허용할 태그 및 속성
     */
    public static function purifyHTML(string $html,array $allowed) {
        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new DOMXPath($dom);
        $allowed = array_merge(["html"=>[],"head"=>[],"body"=>[]],$allowed);
        $query ="//*[not(self::".implode(" or self::",array_keys($allowed)).")]";
        foreach($xpath->query($query) as $node){
            return false;//$node->parentNode->removeChild($node);
        }
        foreach($xpath->query("//@*") as $attribute) {
            if(!in_array($attribute->nodeName,$allowed[$attribute->ownerElement->nodeName] ?? [])) {
                return false;//$attribute->ownerElement->removeAttributeNode($attribute);
            }
        }
        //$body = $dom->getElementsByTagName("body")->item(0);$result = "";
        //foreach($body->childNodes as $child){$result .= $dom->saveHTML($child);}
        return true;//$result;
    }
    /**
     * XSS 공격 필터링 for articles
     * @param string $html 필터링할 입력 값
     */
    public static function purifyArticle(string $html){
        return self::purifyHTML($html,[
            "article"=>["class"],"p"=>["id"],"ul"=>["class"],"ol"=>["class"],"li"=>["class"],"caption"=>["class"]
            ,"img"=>["src","alt","width","height","ismap","loading"],"figure"=>["class"],"figcaption"=>["class"]
            ,"h1"=>["class"],"h2"=>["class"],"h3"=>["class"],"h4"=>["class"],"h5"=>["class"],"h6"=>["class"]
            ,"table"=>["class"],"tr"=>["class"],"th"=>["class","scope"],"td"=>["class"],"colgroup"=>["class"],"col"=>["class"]
        ]);
    }
}