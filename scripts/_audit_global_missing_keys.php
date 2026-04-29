<?php
$files = glob('resources/views/**/*.blade.php', GLOB_BRACE);
$used=[];
foreach($files as $f){$c=file_get_contents($f);preg_match_all("/ui_phrase\\('([^']+)'/",$c,$m);foreach(($m[1]??[]) as $k){$used[$k]=true;}}
$en=include 'lang/en/ui_core.php'; $hs=include 'lang/zh_Hans/ui_core.php'; $ht=include 'lang/zh_Hant/ui_core.php';
$miss=[];
foreach(array_keys($used) as $k){$ev=$en[$k]??null; if($ev===null){$miss[]=$k;continue;} $sv=$hs[$k]??null; $tv=$ht[$k]??null; if($sv===null||$tv===null||trim((string)$sv)===''||trim((string)$tv)==='') $miss[]=$k;}
sort($miss); foreach($miss as $k) echo $k,"\n";
