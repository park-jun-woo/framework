<?php
$entity = &$this->source["entities"][$sequence["entity"]];
$condition = "";
//$condition = str_replace("","",$sequence["condition"]);
$controllerCode .= "        \$result[\"{$sequence["result"]}\"] = \$this->get(\"{$sequence["entity"]}\",\"{$condition}\");\n";
?>