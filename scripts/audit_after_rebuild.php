<?php
$en=include __DIR__.'/../lang/en/ui_core.php';
$h=include __DIR__.'/../lang/zh_Hant/ui_core.php';
$s=include __DIR__.'/../lang/zh_Hans/ui_core.php';
$asciiOnly = fn(string $v)=>preg_match('/^[\x00-\x7F\s[:punct:]]*$/',$v)===1;
$emptyH=[];$emptyS=[];$asciiH=[];$asciiS=[];
foreach($en as $k=>$v){
  $hv=(string)($h[$k]??''); $sv=(string)($s[$k]??'');
  if(trim($hv)==='') $emptyH[]=$k; elseif($asciiOnly($hv)) $asciiH[]=$k;
  if(trim($sv)==='') $emptyS[]=$k; elseif($asciiOnly($sv)) $asciiS[]=$k;
}
echo 'emptyH='.count($emptyH).PHP_EOL;
echo 'emptyS='.count($emptyS).PHP_EOL;
echo 'asciiH='.count($asciiH).PHP_EOL;
echo 'asciiS='.count($asciiS).PHP_EOL;

echo "-- empty keys --\n";
foreach($emptyH as $k){ echo $k.PHP_EOL; }

echo "-- ascii sample H --\n";
for($i=0;$i<80 && $i<count($asciiH);$i++){ $k=$asciiH[$i]; echo $k.' => '.$h[$k].PHP_EOL; }
