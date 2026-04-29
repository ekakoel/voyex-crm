<?php
$root=__DIR__.'/../app/Http/Controllers';
$it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
$c=0;
foreach($it as $f){
 if(!$f->isFile()||$f->getExtension()!=='php') continue;
 $p=$f->getPathname(); $s=file_get_contents($p); $o=$s;
 $s=preg_replace_callback("/ui_phrase\(\s*'([^']*_[^']*)'\s*([,)])/",function($m)use(&$c){$k=trim(preg_replace('/\s+/',' ',str_replace('_',' ',$m[1])));$k=str_replace("'","\\'",$k);$c++;return "ui_phrase('{$k}'{$m[2]}";},$s);
 if($s!==$o) file_put_contents($p,$s);
}
echo "replaced={$c}\n";
