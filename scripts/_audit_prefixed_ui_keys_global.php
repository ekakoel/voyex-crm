<?php
$files = glob('resources/views/**/*.blade.php', GLOB_BRACE);
$pref=[];
foreach($files as $f){$c=file_get_contents($f);preg_match_all("/ui_phrase\\('([^']+)'/",$c,$m);foreach(($m[1]??[]) as $k){if(preg_match('/^[a-z0-9]+([._][a-z0-9]+)+$/',$k)){$pref[$k]=true;}}}
ksort($pref); foreach(array_keys($pref) as $k) echo $k,"\n";
