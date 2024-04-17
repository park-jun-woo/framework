<?php
$entity = &$this->source["entities"][$sequence["entity"]];
foreach($sequence["value"] as $attrName){
    if($sequence["entity"]==$attrName){
        $value = $this->print([
            "required"=>["ko"=>"페이지를 찾을 수 없습니다."],
            "regex:/^{a-zA-Z0-9}+$/"=>["ko"=>"페이지를 찾을 수 없습니다."]
        ],"    ",PHP_EOL,140,3);
        $controllerCode .= "        \$this->validate(\"key\",[{$value}]);\n";
    }else{
        $value = $this->print($entity["attributes"][$attrName]["options"],"    ",PHP_EOL,140,3);
        $controllerCode .= "        \$this->validate(\"$attrName\",[{$value}]);\n";
    }
}
?>