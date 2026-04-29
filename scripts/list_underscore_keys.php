<?php
$a=include __DIR__.'/../lang/en/ui_core.php';
foreach($a as $k=>$v){ if(str_contains($k,'_')) echo $k.PHP_EOL; }
