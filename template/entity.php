<?php
$entityCode = [
    "attributes"=>[
        "name"=>[
            "define"=>"text","name"=>"name","title"=>"이름","options"=>[
                "required"=>["ko"=>"이름을 입력하세요."],
                "max:60"=>["ko"=>"이름은 :max글자까지만 가능합니다."],
                "regex:/^{a-zA-Z0-9가-힣ㄱ-ㅎㅏ-ㅣ\-_.}+$/"=>["ko"=>"이름은 한글, 영문, 숫자, 일부 특수문자(-_.)만 가능합니다."]
            ]
        ],
        "create_date"=>["define"=>"datetime","name"=>"create_date","title"=>"등록일","datetype"=>"solar","timezone"=>"Asia/Seoul","format"=>"Y-m-d H:i:s"],
        "update_date"=>["define"=>"datetime","name"=>"update_date","title"=>"수정일","datetype"=>"solar","timezone"=>"Asia/Seoul","format"=>"Y-m-d H:i:s"]
    ]
];
?>