<?php
$files = [
'resources/views/layouts/master.blade.php',
'resources/views/layouts/navigation.blade.php',
];
foreach (glob('resources/views/modules/customers/*.blade.php') as $f) $files[]=$f;
foreach (glob('resources/views/modules/inquiries/*.blade.php') as $f) $files[]=$f;
foreach (glob('resources/views/modules/itineraries/*.blade.php') as $f) $files[]=$f;
$keys=[];
foreach($files as $f){
  $c=file_get_contents($f);
  preg_match_all("/ui_phrase\\('([^']+)'/", $c, $m);
  foreach(($m[1]??[]) as $k){$keys[$k]=true;}
}
ksort($keys);
$en=include 'lang/en/ui_core.php';
$zhs=include 'lang/zh_Hans/ui_core.php';
$zht=include 'lang/zh_Hant/ui_core.php';
foreach(array_keys($keys) as $k){
  $ev=$en[$k]??null; $sv=$zhs[$k]??null; $tv=$zht[$k]??null;
  $badS = ($sv===null)||($ev!==null && $sv===$ev);
  $badT = ($tv===null)||($ev!==null && $tv===$ev);
  if($badS || $badT){
    echo $k, "\n";
  }
}
