<?php
    $myfile = fopen("newfile.txt", "a") or die("Unable to open file!");
    $txt = "The time is ". date("h:i:s d.m. Y") ."\n";
    fwrite($myfile, $txt);
    fclose($myfile);
?>
