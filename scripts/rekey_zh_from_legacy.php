<?php
$en = include __DIR__.'/../lang/en/ui_core.php';
$hOld = include __DIR__.'/../lang/zh_Hant/ui_core.php';
$sOld = include __DIR__.'/../lang/zh_Hans/ui_core.php';
$legacy = include __DIR__.'/../config/ui_legacy_map.php';

$hNew=[]; $sNew=[];
foreach($en as $phrase => $enVal){
  $hNew[$phrase]='';
  $sNew[$phrase]='';
}

foreach($legacy as $oldKey => $phrase){
  if(!array_key_exists($phrase,$en)) continue;
  if($hNew[$phrase]==='' && array_key_exists($oldKey,$hOld) && trim((string)$hOld[$oldKey])!=='') $hNew[$phrase]=(string)$hOld[$oldKey];
  if($sNew[$phrase]==='' && array_key_exists($oldKey,$sOld) && trim((string)$sOld[$oldKey])!=='') $sNew[$phrase]=(string)$sOld[$oldKey];
}

$filledH=0;$filledS=0;$missH=0;$missS=0;
foreach($en as $phrase=>$v){
  if($hNew[$phrase]!=='') $filledH++; else $missH++;
  if($sNew[$phrase]!=='') $filledS++; else $missS++;
}

echo "filledH=$filledH missH=$missH\n";
echo "filledS=$filledS missS=$missS\n";

ksort($hNew,SORT_NATURAL|SORT_FLAG_CASE);
ksort($sNew,SORT_NATURAL|SORT_FLAG_CASE);
file_put_contents(__DIR__.'/../lang/zh_Hant/ui_core.php',"<?php\n\nreturn ".var_export($hNew,true).";\n");
file_put_contents(__DIR__.'/../lang/zh_Hans/ui_core.php',"<?php\n\nreturn ".var_export($sNew,true).";\n");
