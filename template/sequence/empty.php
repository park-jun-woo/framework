<?php
//$controllerCode .= "        //empty\n";
foreach($sequence["value"] as $attrName=>$attrMessage){
    $messageCode = $this->addMessage($attrMessage);
    $keys = explode(".",$attrName);
    switch(count($keys)){
    case 2:
        switch($keys[0]){
        case "result":
            $controllerCode .= "        \$this->empty(\$result,\"{$keys[1]}\",{$messageCode});\n";
            break;
        }
        break;
    }
}
?>