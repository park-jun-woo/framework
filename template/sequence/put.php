<?php
$entity = &$this->source["entities"][$sequence["entity"]];
$singleName = $inflector->singularize($sequence["entity"]);
$condition = "\"key=\".\$this->request->param(\"{$sequence["entity"]}\")";
$controllerCode .= "        \$values = [
";
foreach($sequence["value"] as $attr=>$param){
    if(strpos($param,".")!==false){
        $postEx = explode(".",$param);
        switch($postEx[0]){
            case "request":
                $var = "\$this->request->param(\"{$postEx[1]}\")";
                $controllerCode .= "            \"{$attr}\"=>{$var},\n";
                break;
            case "result":
                $var = "\$this->result[\"{$postEx[1]}\"][\"key\"]";
                $controllerCode .= "            \"{$attr}\"=>{$var},\n";
                break;
            case "user":
                $var = "\$this->user->{$postEx[1]}()";
                $controllerCode .= "            \"{$attr}\"=>{$var},\n";
                break;
            default:
                break;
        }
    }else{
        $controllerCode .= "            \"{$attr}\"=>\"{$param}\",\n";
    }
}
$controllerCode .= "        ];\n";
$controllerCode .= "        \$this->{$singleName}->put(\$values,{$condition});\n";
?>