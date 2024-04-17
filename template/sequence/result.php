<?php
if(array_key_exists("html",$sequence)){
    $controllerCode .= "        \$this->result(\"{$sequence["html"]}\");";
}
if(array_key_exists("redirect",$sequence)){
    $controllerCode .= "        \$this->redirect(\"{$sequence["redirect"]}\");";
}
?>