<?php
$files = [
'resources/views/dashboard.blade.php',
'resources/views/admin/dashboard.blade.php',
'resources/views/administrator/dashboard.blade.php',
'resources/views/director/dashboard.blade.php',
'resources/views/editor/dashboard.blade.php',
'resources/views/finance/dashboard.blade.php',
'resources/views/manager/dashboard.blade.php',
'resources/views/marketing/dashboard.blade.php',
'resources/views/operations/dashboard.blade.php',
'resources/views/reservation/dashboard.blade.php',
'resources/views/sales/dashboard.blade.php',
'resources/views/superadmin/dashboard.blade.php',
];
foreach (glob('resources/views/administrator/dashboard/partials/*.blade.php') as $f) $files[]=$f;
$keys=[];
foreach($files as $f){if(!is_file($f))continue;$c=file_get_contents($f);preg_match_all("/ui_phrase\\('([^']+)'/",$c,$m);foreach(($m[1]??[]) as $k){$keys[$k]=true;}}
$en=include 'lang/en/ui_core.php'; $hs=include 'lang/zh_Hans/ui_core.php'; $ht=include 'lang/zh_Hant/ui_core.php';
$miss=[];
foreach(array_keys($keys) as $k){$ev=$en[$k]??null; if($ev===null){$miss[]=$k; continue;} $sv=$hs[$k]??null; $tv=$ht[$k]??null; if($sv===null||$tv===null||$sv===$ev||$tv===$ev||trim((string)$sv)===''||trim((string)$tv)==='') $miss[]=$k;}
sort($miss); foreach($miss as $k) echo $k,"\n";
