<?php
if(array_key_exists("html",$sequence)){
    $vars = "";
    if(array_key_exists("value",$sequence)){
        foreach($sequence["value"] as $val){
            if(strpos($val,".")!==false){
                $exploded = explode(".",$val);
                switch($exploded[0]){
                case "result":
                    if($vars!=""){$vars .= ",";}
                    $vars .= "\$this->result[\"{$exploded[1]}\"]";
                    break;
                }
            }
        }
    }
    if($vars!=""){$vars = ",[$vars]";}
    $controllerCode .= "        \$this->result(\"{$sequence["html"]}\"{$vars});";
}
if(array_key_exists("redirect",$sequence)){
    $controllerCode .= "        \$this->redirect(\"{$sequence["redirect"]}\");";
}
?>