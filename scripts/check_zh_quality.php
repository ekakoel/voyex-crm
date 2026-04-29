<?php
$h=include __DIR__.'/../lang/zh_Hant/ui_core.php';
$s=include __DIR__.'/../lang/zh_Hans/ui_core.php';
$emptyH=0;$emptyS=0;$latinH=0;$latinS=0;
foreach($h as $k=>$v){$t=(string)$v; if(trim($t)==='')$emptyH++; if(preg_match('/[A-Za-z]/',$t))$latinH++;}
foreach($s as $k=>$v){$t=(string)$v; if(trim($t)==='')$emptyS++; if(preg_match('/[A-Za-z]/',$t))$latinS++;}
echo "emptyH=$emptyH\nemptyS=$emptyS\nlatinH=$latinH\nlatinS=$latinS\n";
