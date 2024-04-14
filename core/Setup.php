<?php
namespace core;

use util\Debug;
use util\File;
use util\Image;

class Setup{
    public function __construct(array $env){
        echo "Init Setup {$env['PROJECT_NAME']}\n";
        print_r($env);
    }
}

?>