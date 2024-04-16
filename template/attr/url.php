<?php
$attrCode = [
    "define"=>"text","title"=>"$attrTitle",
    "options"=>[
        "required"=>["ko"=>"{$attrTitle}를 입력하세요."],
        "max:2048"=>["ko"=>"{$attrTitle}는 :max글자까지만 가능합니다."],
        "regex:/(ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/"=>["ko"=>"{$attrTitle}는 URL만 가능합니다."]
    ]
];
$attrValue = "request.$attrName";
$attrTag = "<$attrName></$attrName>";
$attrFormTag = "<input type=\"text\" name=\"$attrName\" value=\"{{{$attrName}}}\">";
?>