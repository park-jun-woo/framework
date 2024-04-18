<?php
$entity = &$this->source["entities"][$sequence["entity"]];
$singleName = $inflector->singularize($sequence["entity"]);
$condition = "";
//$condition = str_replace("","",$sequence["condition"]);
$controllerCode .= "        \$this->result[\"{$sequence["result"]}\"] = \$this->{$singleName}->get(\"{$condition}\");\n";
?>