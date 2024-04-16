<?php
require_once "vendor/autoload.php";
require_once "core/Setup.php";
require_once "util/Debug.php";
require_once "util/File.php";
require_once "util/Image.php";

use Doctrine\Inflector\InflectorFactory;
use core\Setup;
use util\Debug;
use util\File;

if(!isset($env)){echo "Invalid access.\n";exit;}
    
$inflector = InflectorFactory::create()->build();

$path_root = $env["PATH_ROOT"].DIRECTORY_SEPARATOR;
$path_source = $env["PATH_SOURCE"].DIRECTORY_SEPARATOR;
$path_core = $env["PATH_CORE"].DIRECTORY_SEPARATOR."core".DIRECTORY_SEPARATOR;
$path_util = $env["PATH_CORE"].DIRECTORY_SEPARATOR."util".DIRECTORY_SEPARATOR;
$path_template = $env["PATH_CORE"].DIRECTORY_SEPARATOR."template".DIRECTORY_SEPARATOR;
$path_vendor = $env["PATH_CORE"].DIRECTORY_SEPARATOR."vendor".DIRECTORY_SEPARATOR;

if(file_exists($path_source."code.php")){include $path_source."code.php";}
else{include $path_template."code.php";}

switch($argv[1]){
    case "setup":
        if(count($argv)!=2){error("Invalid arguments!\n'setup' must be 2 arvs.");}
        new Setup($env);
        break;
    case "exec":
        /*
        $list = File::read($path_template."sample.json");
        $list = json_decode($list,true);
        $template = "<?php
\$listCode = ".Debug::print($list,"    ").";
?>";
        File::write($path_template."list.php",$template);
        //*/
        /*
        foreach($code["entities"] as $entityId=>&$entity){
            $attributes = [];
            unset($entity["name"]);
            foreach($entity["attributes"] as &$attribute){
                $name = $attribute["name"];
                unset($attribute["name"]);
                $attributes[$name] = $attribute;
            }
            $entity["attributes"] = $attributes;
        }
$template = "<?php
\$code = ".Debug::print($code,"    ").";
?>";
File::write($path_template."code.php",$template);
        //*/
        exit;
    case "user":
        if(count($argv)!=3){error("Invalid arguments!\n'app' must be 3 arvs.");}
        $userName = $argv[2];
        if(array_key_exists($userName,$code["user"])){echo "The user code already has '{$userName}'.\n";exit;}
        else{
            $last = 0;
            $users = [];
            foreach($code["user"] as $user=>$permission){
                switch($user){
                    case "writer":case "admin":case "system":break;
                    default:
                        if($last<$permission){$last = $permission;}
                        $users[$user] = $permission;
                        break;
                }
            }
            $users[$userName] = $last+1;
            $users["writer"] = 2305843009213693952;
            $users["admin"] = 4611686018427387904;
            $users["system"] = -9223372036854775808;
            $code["user"] = $users;
        }
        break;
    case "app":
        if(count($argv)!=3){error("Invalid arguments!\n'app' must be 3 arvs.");}
        addApp($argv[2]);
        break;
    case "page":
        switch(count($argv)){
            default:error("Invalid arguments!\n'page' must be 3 or 4 arvs.");
            case 3:addPage($argv[2]);break;
            case 4:addPage($argv[3],$argv[2]);break;
        }
        break;
    case "list":
        if(count($argv)!=3){error("Invalid arguments!\n'list' must be 3 arvs.");}
        addList($argv[2]);
        break;
    default:
        //php make text articles heading 표제
        switch(count($argv)){
            default:error("Invalid arguments!\n'{$argv[0]}' must be 4 or 5 arvs.");
            case 4:addAttr($argv[1],$argv[2],$argv[3]);break;
            case 5:addAttr($argv[1],$argv[2],$argv[3],$argv[4]);break;
        }
        break;
}

//code.php 저장하기
$template = "<?php
\$code = ".Debug::print($code,"    ").";
?>";
File::write($path_source."code.php",$template);

//앱 추가
function addApp($appName){
    GLOBAL $env,$path_root,$path_source,$path_template,$code;
    //앱 폴더 없으면 생성
    if(!is_dir($path_root.$appName)){mkdir($path_root.$appName);}
    if(!is_dir($path_root.$appName.DIRECTORY_SEPARATOR."images"))
    {mkdir($path_root.$appName.DIRECTORY_SEPARATOR."images");}
    if(!is_dir($path_source.$appName)){mkdir($path_source.$appName);}
    if(!array_key_exists($appName,$code)){
        include $path_template."app.php";
        $code[$appName] = $appCode;
    }
    $template = File::read($path_template."index.php");
    File::write($path_root.$appName.DIRECTORY_SEPARATOR."index.php",$template);
}

//페이지 추가
function addPage($pageName,$appName=""){
    GLOBAL $env,$path_root,$path_source,$path_template,$code;
    $apps = [];
    if($appName==""){
        foreach($code as $key=>&$value){
            switch($key){
                case "user":case "entities":break;
                default:array_push($apps,$key);break;
            }
        }
    }else{
        if(!array_key_exists($appName,$code)){addApp($appName);}
        array_push($apps,$appName);
    }
    foreach($apps as $appName){
        $path_app = $path_source.$appName.DIRECTORY_SEPARATOR;
        $route = "/$pageName";
        if(array_key_exists($route,$code[$appName])){echo "App code {$appName} already has '{$route}'.\n";}
        else{
            include $path_template."page.php";
            $code[$appName][$route] = $pageCode;
        }
        //페이지 html 없으면 생성
        $createPath = $path_app.$pageName.".html";
        if(file_exists($createPath)){echo "{$createPath} already exists.\n";}
        else{
            $template = File::read($path_template."html".DIRECTORY_SEPARATOR."page.html");
            $template = str_replace(["{{title}}","{{description}}"],[$env["PROJECT_NAME"],$env["PROJECT_DESCRIPTION"]],$template);
            File::write($createPath,$template);
        }
    }
}

//목록 추가
function addList($pageName,$appName=""){
    GLOBAL $env,$inflector,$path_root,$path_source,$path_template,$code;
    $apps = [];
    if($appName==""){
        foreach($code as $key=>&$value){
            switch($key){
                case "user":case "entities":break;
                default:array_push($apps,$key);break;
            }
        }
    }else{
        if(!array_key_exists($appName,$code)){addApp($appName);}
        array_push($apps,$appName);
    }
    $sName = $inflector->singularize($pageName);
    $pName = $inflector->pluralize($pageName);
    //엔티티 추가
    $entityName = $pName;
    if(array_key_exists($entityName,$code["entities"])){echo "Entity code already has '{$entityName}'.\n";}
    else{
        include $path_template."entity.php";
        $code["entities"][$entityName] = $entityCode;
    }
    //코드 추가
    foreach($apps as $appName){
        include $path_template."list.php";
        foreach($listCode as $route=>&$pageCode){
            if(array_key_exists($route,$code[$appName])){echo "App code {$appName} already has '{$route}'.\n";}
            else{$code[$appName][$route] = $pageCode;}
        }

        $path_app = $path_source.$appName.DIRECTORY_SEPARATOR;
        $createPath = $path_app.$pName.".html";
        if(file_exists($createPath)){echo "{$createPath} already exists.\n";}
        else{
            $template = File::read($path_template."html".DIRECTORY_SEPARATOR."list.html");
            $template = str_replace(["{{title}}","{{description}}"],[$env["PROJECT_NAME"],$env["PROJECT_DESCRIPTION"]],$template);
            File::write($createPath,$template);
        }

        $createPath = $path_app."detail-".$sName.".html";
        if(file_exists($createPath)){echo "{$createPath} already exists.\n";}
        else{
            $template = File::read($path_template."html".DIRECTORY_SEPARATOR."detail.html");
            $template = str_replace(["{{title}}","{{description}}"],[$env["PROJECT_NAME"],$env["PROJECT_DESCRIPTION"]],$template);
            File::write($createPath,$template);
        }

        $createPath = $path_app."new-".$sName.".html";
        if(file_exists($createPath)){echo "{$createPath} already exists.\n";}
        else{
            $template = File::read($path_template."html".DIRECTORY_SEPARATOR."create.html");
            $template = str_replace(["{{title}}","{{description}}"],[$env["PROJECT_NAME"],$env["PROJECT_DESCRIPTION"]],$template);
            File::write($createPath,$template);
        }

        $createPath = $path_app."modify-".$sName.".html";
        if(file_exists($createPath)){echo "{$createPath} already exists.\n";}
        else{
            $template = File::read($path_template."html".DIRECTORY_SEPARATOR."modify.html");
            $template = str_replace(
                ["{{title}}","{{description}}","{{key}}"],
                [$env["PROJECT_NAME"],$env["PROJECT_DESCRIPTION"],$pName
            ],$template);
            File::write($createPath,$template);
        }
    }
}

function addAttr(string $define, string $pageName, string $attrName, string $attrTitle=""){
    GLOBAL $env,$inflector,$path_root,$path_source,$path_template,$code;
    
    $sName = $inflector->singularize($pageName);
    $pName = $inflector->pluralize($pageName);
    $path_define = $path_template."attr".DIRECTORY_SEPARATOR.$define.".php";
    include $path_define;

    if(!array_key_exists($pName,$code["entities"])){addList($pageName);}
    if(array_key_exists($attrName, $code["entities"][$pName]["attributes"])){
        echo "Entity '$pName' code already has '{$attrName}'.\n";
    }else{
        if(!file_exists($path_define)){
            echo "The '$define' attribute type is not exists.\n";exit;
        }else{
            $code["entities"][$pName]["attributes"][$attrName] = $attrCode;
        }
    }
    
    $apps = [];
    foreach($code as $key=>&$value){
        switch($key){
            case "user":case "entities":break;
            default:array_push($apps,$key);break;
        }
    }

    //코드 추가
    foreach($apps as $appName){
        if(array_key_exists("/$pName",$code[$appName])){
            if(array_key_exists("post",$code[$appName]["/$pName"])){
                foreach($code[$appName]["/$pName"]["post"]["code"] as &$sequence){
                    switch($sequence["method"]){
                        case "validate":array_push($sequence["value"],$attrName);break;
                        case "post":$sequence["value"][$attrName] = $attrValue;break;
                    }
                }
            }
        }
        if(array_key_exists("/$pName/[$pName]",$code[$appName])){
            if(array_key_exists("put",$code[$appName]["/$pName/[$pName]"])){
                foreach($code[$appName]["/$pName/[$pName]"]["put"]["code"] as &$sequence){
                    switch($sequence["method"]){
                        case "validate":array_push($sequence["value"],$attrName);break;
                        case "put":$sequence["value"][$attrName] = $attrValue;break;
                    }
                }
            }
        }
        $path_app = $path_source.$appName.DIRECTORY_SEPARATOR;

        $path_html = $path_app.$pName.".html";
        if(file_exists($path_html)){
            $template = File::read($path_html);
            $template = str_replace("            </li>","                $attrTag\n            </li>",$template);
            File::write($path_html,$template);
        }

        $path_html = $path_app."detail-".$sName.".html";
        if(file_exists($path_html)){
            $template = File::read($path_html);
            $template = str_replace("        </section>","            $attrTag\n        </section>",$template);
            File::write($path_html,$template);
        }
        
        $path_html = $path_app."new-".$sName.".html";
        if(file_exists($path_html)){
            $template = File::read($path_html);
            $template = str_replace("            <button","            $attrFormTag\n            <button",$template);
            File::write($path_html,$template);
        }
        
        $path_html = $path_app."modify-".$sName.".html";
        if(file_exists($path_html)){
            $template = File::read($path_html);
            $template = str_replace("            <button","            $attrFormTag\n            <button",$template);
            File::write($path_html,$template);
        }
    }
}

function error(string $message){
    if($message!=""){echo "Error: $message\n";}
    echo "Usage: php make [command] [app] [page]\n";
    echo "Ex: php make setup\n";
    echo "Ex: php make page admin test\n";
    echo "\n[Commands]\n";
    echo "setup: Setup this application.\n";
    echo "user: add user.\n";
    echo "app: add application.\n";
    echo "page: single page.\n";
    echo "list: list pages.\n";
    exit;
}
?>