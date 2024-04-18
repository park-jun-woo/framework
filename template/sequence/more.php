<?php
$entity = &$this->source["entities"][$sequence["entity"]];
$singleName = $inflector->singularize($sequence["entity"]);
$controllerCode .= "        \$this->{$singleName}->more({$sequence["items"]});\n";
?>