<?php

$folder = "galaxys";

if ($handle = opendir($folder)) {
    while (false !== ($file = readdir($handle))) {
        if ($file != "." && $file != "..") {
            echo "<a href=\"gen.php?file=$file&v\">$file</a><br>";
        }
    }
    closedir($handle);
}