<?php
use Whis\Storage\Storage;


function return_bytes(string $val=null) {
    //var_dump($val);
    $valString=(string)($val);
    $val = (int)trim($val);
    $last = strtolower($valString[strlen($valString)-1]);
    //var_dump($last);
    switch($last) {
        // The 'G' modifier is available
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
    }

    return $val;
}
