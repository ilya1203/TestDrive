<?php

    $files = scandir(__DIR__."/");
    foreach($files as $file)
    {
        if(strpos($file, ".php") != false)
        {
            if($file != "autoload.php")
                require_once __DIR__.'/'.$file;
        }
    }

?>