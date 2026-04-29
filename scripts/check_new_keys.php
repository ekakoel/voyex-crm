<?php
foreach (['en','zh_Hant','zh_Hans'] as $loc) {
  $a = include __DIR__ . '/../lang/'.$loc.'/ui_core.php';
  $u=0; $pref=0;
  foreach(array_keys($a) as $k){ if(str_contains($k,'_')) $u++; if(preg_match('/^(common|modules|shared phrases|admin dashboard|manager dashboard|director dashboard)_/i',$k)) $pref++; }
  echo $loc.' keys='.count($a).' underscore_keys='.$u.' pref_like='.$pref.PHP_EOL;
}
