<?php
$h=include __DIR__.'/../lang/zh_Hant/ui_core.php';
foreach($h as $k=>$v){ if(preg_match('/[A-Za-z]/',(string)$v)) echo $k." => ".$v.PHP_EOL; }
