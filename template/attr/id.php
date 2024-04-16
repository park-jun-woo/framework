<?php
$attrCode = [
    "define"=>"text","title"=>"$attrTitle",
    "options"=>[
        "required"=>["ko"=>"{$attrTitle}를 입력하세요."],
        "min:3"=>["ko"=>"{$attrTitle}는 :min글자 이상만 가능합니다."],
        "max:60"=>["ko"=>"{$attrTitle}는 :max글자까지만 가능합니다."],
        "regex:/^{a-zA-Z0-9\-_.}+$/"=>["ko"=>"{$attrTitle}는 영문, 숫자, 일부 특수문자(-_.)만 가능합니다."]
    ]
];
$attrValue = "request.$attrName";
$attrTag = "<$attrName></$attrName>";
$attrFormTag = "<input type=\"text\" name=\"$attrName\" value=\"{{{$attrName}}}\">";
?>