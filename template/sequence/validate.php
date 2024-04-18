<?php
$entity = &$this->source["entities"][$sequence["entity"]];
foreach($sequence["value"] as $attrName){
    if($sequence["entity"]==$attrName){
        $messageCode = $this->addMessage(["ko"=>"페이지를 찾을 수 없습니다."]);
        $controllerCode .= "        \$this->validate(\"{$sequence["entity"]}\",\"required\",{$messageCode});\n";
        $controllerCode .= "        \$this->validate(\"regex:/^{a-zA-Z0-9}+$/\",\"required\",{$messageCode});\n";
    }else{
        foreach($entity["attributes"][$attrName]["options"] as $key=>&$value){
            $messageCode = $this->addMessage($value);
            $controllerCode .= "        \$this->validate(\"$attrName\",\"{$key}\",{$messageCode});\n";
        }
    }
}
?>