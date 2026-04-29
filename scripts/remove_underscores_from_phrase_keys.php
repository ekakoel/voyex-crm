<?php
$en=include __DIR__.'/../lang/en/ui_core.php';
$h=include __DIR__.'/../lang/zh_Hant/ui_core.php';
$s=include __DIR__.'/../lang/zh_Hans/ui_core.php';
$legacy=include __DIR__.'/../config/ui_legacy_map.php';

$rekey = function(array $arr, array &$legacyMap): array {
  $out=[];
  foreach($arr as $k=>$v){
    $nk = str_replace('_',' ',(string)$k);
    if(!array_key_exists($nk,$out)) $out[$nk]=$v;
    if($nk !== $k) {
      $legacyMap[$k]=$nk;
    }
  }
  ksort($out, SORT_NATURAL|SORT_FLAG_CASE);
  return $out;
};

$en=$rekey($en,$legacy);
$h=$rekey($h,$legacy);
$s=$rekey($s,$legacy);
ksort($legacy, SORT_NATURAL|SORT_FLAG_CASE);

$write=function($p,$a){file_put_contents($p,"<?php\n\nreturn ".var_export($a,true).";\n");};
$write(__DIR__.'/../lang/en/ui_core.php',$en);
$write(__DIR__.'/../lang/zh_Hant/ui_core.php',$h);
$write(__DIR__.'/../lang/zh_Hans/ui_core.php',$s);
$write(__DIR__.'/../config/ui_legacy_map.php',$legacy);

echo 'done'.PHP_EOL;
