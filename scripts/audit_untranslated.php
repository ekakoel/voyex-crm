<?php
$en=include __DIR__.'/../lang/en/ui_core.php';
$h=include __DIR__.'/../lang/zh_Hant/ui_core.php';
$s=include __DIR__.'/../lang/zh_Hans/ui_core.php';

$asciiOnly=function(string $v): bool { return preg_match('/^[\x00-\x7F\s[:punct:]]*$/',$v)===1; };

$hMissing=[];$sMissing=[];
foreach($en as $k=>$ev){
  $hv=(string)($h[$k]??'');
  $sv=(string)($s[$k]??'');
  if($hv===''||$asciiOnly($hv)) $hMissing[$k]=$hv;
  if($sv===''||$asciiOnly($sv)) $sMissing[$k]=$sv;
}

echo 'h_missing_or_ascii='.count($hMissing).PHP_EOL;
echo 's_missing_or_ascii='.count($sMissing).PHP_EOL;

$i=0; echo "\n-- zh_Hant sample --\n";
foreach($hMissing as $k=>$v){ echo $k.' => '.$v.PHP_EOL; if(++$i>=120) break; }
$i=0; echo "\n-- zh_Hans sample --\n";
foreach($sMissing as $k=>$v){ echo $k.' => '.$v.PHP_EOL; if(++$i>=120) break; }
