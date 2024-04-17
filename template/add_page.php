<?php
$pageCode = [
    "get"=>[
        "permission"=>[$appName=="admin"?"admin":"guest"],
        "code"=>[["method"=>"result","html"=>$pageName]]
    ]
];
?>