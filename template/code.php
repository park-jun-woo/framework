<?php
$code = [
    "user"=>["guest"=>0,"member"=>1,"staff"=>2,"writer"=>2305843009213693952,"admin"=>4611686018427387904,"system"=>-9.2233720368548E+18],
    "entities"=>[
        "members"=>[
            "title"=>"회원",
            "attributes"=>[
                "id"=>[
                    "define"=>"text",
                    "title"=>"아이디",
                    "required"=>"yes",
                    "options"=>[
                        "required"=>["ko"=>"아이디를 입력하세요."],
                        "min:3"=>["ko"=>"아이디는 :min글자 이상만 가능합니다."],
                        "max:60"=>["ko"=>"아이디는 :max글자까지만 가능합니다."],
                        "regex:/^{a-zA-Z0-9\-_.}+$/"=>["ko"=>"아이디는 영문, 숫자, 일부 특수문자(-_.)만 가능합니다."]
                    ]
                ],
                "password"=>["define"=>"password","title"=>"비밀번호","required"=>"yes","encryption"=>"sha512"],
                "name"=>["define"=>"text","title"=>"이름","required"=>"yes","max"=>60,"regex"=>"/^{a-zA-Z0-9\-_.}+$/"],
                "email"=>["define"=>"email","title"=>"이메일","required"=>"yes"],
                "gender"=>["define"=>"gender","title"=>"성별","required"=>"no"],
                "pause"=>["define"=>"boolean","title"=>"정지","default"=>"no"],
                "removed"=>["define"=>"boolean","title"=>"강퇴","default"=>"no"],
                "create_date"=>["define"=>"datetime","title"=>"가입일","datetype"=>"solar","timezone"=>"Asia/Seoul","format"=>"Y-m-d H:i:s"],
                "latest_login"=>["define"=>"latest","title"=>"최근 로그인","entity"=>"logins","attribute"=>"login_date","condition"=>"succeeded='yes'"],
                "latest_logout"=>["define"=>"latest","title"=>"최근 로그아웃","entity"=>"logins","attribute"=>"logout_date","condition"=>"succeeded='yes'"],
                "latest_password"=>["define"=>"latest","title"=>"최근 비밀번호 변경","entity"=>"passwords","attribute"=>"write_date"]
            ]
        ],
        "devices"=>[
            "title"=>"회원 접속기기",
            "attributes"=>[
                "sessionid"=>["define"=>"text","title"=>"세션아이디","required"=>"yes","max"=>1023],
                "name"=>["define"=>"text","title"=>"기기명","required"=>"no","max"=>64],
                "modelname"=>["define"=>"text","title"=>"모델명","required"=>"no","max"=>64],
                "os"=>["define"=>"text","title"=>"운영체제","required"=>"no","max"=>64],
                "browser"=>["define"=>"text","title"=>"브라우저","required"=>"no","max"=>64],
                "member"=>[
                    "define"=>"parent","title"=>"회원","required"=>"no","entity"=>"members",
                    "attributes"=>["id","name"]
                ],
                "app"=>["define"=>"app","title"=>"접속앱","required"=>"yes"],
                "ip"=>["define"=>"ip","title"=>"접속아이피","required"=>"yes"],
                "location"=>["define"=>"geometry","title"=>"접속위치","required"=>"no"],
                "referer"=>["define"=>"referer","title"=>"리퍼러","required"=>"yes"],
                "create_date"=>["define"=>"datetime","title"=>"등록일","datetype"=>"solar","timezone"=>"Asia/Seoul","format"=>"Y-m-d H:i:s"]
            ]
        ],
        "passwords"=>[
            "title"=>"비밀번호 변경기록",
            "attributes"=>[
                "password"=>["define"=>"password","title"=>"비밀번호","required"=>"yes","encryption"=>"sha512"],
                "member"=>[
                    "define"=>"parent","title"=>"회원","required"=>"yes","entity"=>"members",
                    "attributes"=>["id","name"]
                ],
                "device"=>[
                    "define"=>"parent","title"=>"접속기기","required"=>"yes","entity"=>"devices",
                    "attributes"=>["sessionid","name"]
                ],
                "ip"=>["define"=>"ip","title"=>"접속아이피","required"=>"yes"],
                "location"=>["define"=>"geometry","title"=>"접속위치","required"=>"no"],
                "referer"=>["define"=>"referer","title"=>"리퍼러","required"=>"yes"],
                "create_date"=>["define"=>"datetime","title"=>"등록일","datetype"=>"solar","timezone"=>"Asia/Seoul","format"=>"Y-m-d H:i:s"]
            ]
        ],
        "history"=>[
            "title"=>"회원 기록",
            "attributes"=>[
                "content"=>["define"=>"text","title"=>"내용","required"=>"yes"],
                "member"=>[
                    "define"=>"parent","title"=>"회원","required"=>"yes","entity"=>"members",
                    "attributes"=>["id","name"]
                ],
                "device"=>[
                    "define"=>"parent","title"=>"접속기기","required"=>"yes","entity"=>"devices",
                    "attributes"=>["sessionid","name"]
                ],
                "writer"=>[
                    "define"=>"parent","title"=>"작성자","required"=>"yes","entity"=>"members",
                    "attributes"=>["id","name"]
                ],
                "ip"=>["define"=>"ip","title"=>"접속아이피","required"=>"yes"],
                "location"=>["define"=>"geometry","title"=>"접속위치","required"=>"no"],
                "referer"=>["define"=>"referer","title"=>"리퍼러","required"=>"yes"],
                "create_date"=>["define"=>"datetime","title"=>"등록일","datetype"=>"solar","timezone"=>"Asia/Seoul","format"=>"Y-m-d H:i:s"]
            ]
        ]
    ],
    "admin"=>[
        "/"=>[
            "get"=>[
                "permission"=>["guest"],
                "code"=>[
                    ["method"=>"result","html"=>"welcome"]
                ]
            ]
        ],
        "/home"=>[
            "get"=>[
                "permission"=>["admin"],
                "code"=>[
                    ["method"=>"result","html"=>"home"]
                ]
            ]
        ],
        "/login"=>[
            "post"=>[
                "permission"=>["admin"],
                "code"=>[
                    [
                        "method"=>"validate",
                        "exit"=>"yes",
                        "value"=>[
                            "id"=>[
                                "required"=>["ko"=>"아이디를 입력해주세요."],
                                "min:3"=>["ko"=>"아이디는 적어도 :min글자 이상 가능합니다."],
                                "max:60"=>["ko"=>"아이디는 :max글자까지만 가능합니다."],
                                "regex:/^{a-zA-Z0-9\-_.}+$/"=>["ko"=>"아이디는 영문, 숫자, 일부 특수문자(-_.)만 가능합니다."]
                            ],
                            "password"=>[
                                "required"=>["ko"=>"비밀번호를 입력해주세요."]
                            ]
                        ]
                    ],
                    ["method"=>"get","entity"=>"members","condition"=>"id=request.id","result"=>"member"],
                    [
                        "method"=>"empty","exit"=>"yes",
                        "value"=>[
                            "result.member"=>["ko"=>"아이디가 없거나 비밀번호가 틀렸습니다."]
                        ]
                    ],
                    [
                        "method"=>"post",
                        "entity"=>"login",
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
                        "value"=>[
                            "message"=>["ko"=>"로그인 5회 틀렸습니다."]
                        ]
                    ],
                    [
                        "method"=>"password","exit"=>"yes",
                        "value"=>[
                            "result.member=password"=>["ko"=>"아이디가 없거나 비밀번호가 틀렸습니다."]
                        ]
                    ],
                    ["method"=>"result","redirect"=>"/home"]
                ]
            ]
        ]
    ],
    "public"=>[
        "/"=>[
            "get"=>[
                "permission"=>["guest"],
                "code"=>[
                    ["method"=>"result","html"=>"welcome"]
                ]
            ]
        ],
        "/home"=>[
            "get"=>[
                "permission"=>["member"],
                "code"=>[
                    ["method"=>"result","html"=>"home"]
                ]
            ]
        ],
        "/login"=>[
            "post"=>[
                "permission"=>["guest"],
                "code"=>[
                    [
                        "method"=>"validate",
                        "exit"=>"yes",
                        "value"=>[
                            "id"=>[
                                "required"=>["ko"=>"아이디를 입력해주세요."],
                                "min:3"=>["ko"=>"아이디는 적어도 :min글자 이상 가능합니다."],
                                "max:60"=>["ko"=>"아이디는 :max글자까지만 가능합니다."],
                                "regex:/^{a-zA-Z0-9\-_.}+$/"=>["ko"=>"아이디는 영문, 숫자, 일부 특수문자(-_.)만 가능합니다."]
                            ],
                            "password"=>[
                                "required"=>["ko"=>"비밀번호를 입력해주세요."]
                            ]
                        ]
                    ],
                    ["method"=>"get","entity"=>"members","condition"=>"id=request.id","result"=>"member"],
                    [
                        "method"=>"empty","exit"=>"yes",
                        "value"=>[
                            "result.member"=>["ko"=>"아이디가 없거나 비밀번호가 틀렸습니다."]
                        ]
                    ],
                    [
                        "method"=>"post",
                        "entity"=>"login",
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
                        "value"=>[
                            "message"=>["ko"=>"로그인 5회 틀렸습니다."]
                        ]
                    ],
                    [
                        "method"=>"password","exit"=>"yes",
                        "value"=>[
                            "result.member=password"=>["ko"=>"아이디가 없거나 비밀번호가 틀렸습니다."]
                        ]
                    ],
                    ["method"=>"result","redirect"=>"/home"]
                ]
            ]
        ]
    ]
];
?>