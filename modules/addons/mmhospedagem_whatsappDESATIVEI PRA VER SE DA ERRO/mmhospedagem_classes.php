<?php

class MMHospedagem_Whatsapp
{

	function getHooks()
    {
        if ($handle = opendir(dirname(__FILE__) . '/mmhospedagem_hooks')) {
            while (false !== ($entry = readdir($handle))) {
                if (substr($entry, strlen($entry) - 4, strlen($entry)) == ".php") {
                    $file[] = require_once('mmhospedagem_hooks/' . $entry);
                }
            }
            closedir($handle);
        }
        return $file;
    }

}