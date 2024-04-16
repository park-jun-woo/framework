<?php
$appCode = [
    "/"=>[
        "get"=>["permission"=>["guest"],"code"=>[["method"=>"result","html"=>"welcome"]]]
    ],
    "/home"=>[
        "get"=>["permission"=>[$appName=="admin"?"admin":"member"],"code"=>[["method"=>"result","html"=>"home"]]]
    ],
    "/login"=>[
        "post"=>[
            "permission"=>["guest"],
            "code"=>[
                [
                    "method"=>"validate","exit"=>"yes",
                    "value"=>[
                        "id"=>[
                            "required"=>["ko"=>"아이디를 입력해주세요."],
                            "min:3"=>["ko"=>"아이디는 적어도 :min글자 이상 가능합니다."],
                            "max:60"=>["ko"=>"아이디는 :max글자까지만 가능합니다."],
                            "regex:/^{a-zA-Z0-9\-_.}+$/"=>["ko"=>"아이디는 영문, 숫자, 일부 특수문자(-_.)만 가능합니다."]
                        ],
                        "password"=>["required"=>["ko"=>"비밀번호를 입력해주세요."]]
                    ]
                ],
                ["method"=>"get","entity"=>"members","condition"=>"id=request.id","result"=>"member"],
                [
                    "method"=>"empty","exit"=>"yes",
                    "value"=>["result.member"=>["ko"=>"아이디가 없거나 비밀번호가 틀렸습니다."]]
                ],
                [
                    "method"=>"post","entity"=>"login",
                    "value"=>[
                        "member"=>"result.member",
                        "succeeded"=>"no",
                        "login_date"=>"now()",
                        "logout"=>"no",
                        "logout_date"=>"now()",
                        "ip"=>"user.ip",
                        "referer"=>"user.referer",
                        "location"=>"user.location"
                    ]
                ],
                [
                    "method"=>"try","count"=>5,"period"=>3600,
                    "value"=>["message"=>["ko"=>"로그인 5회 틀렸습니다."]]
                ],
                [
                    "method"=>"password","exit"=>"yes",
                    "value"=>["result.member=password"=>["ko"=>"아이디가 없거나 비밀번호가 틀렸습니다."]]
                ],
                ["method"=>"result","redirect"=>"/home"]
            ]
        ]
    ]
];
?>