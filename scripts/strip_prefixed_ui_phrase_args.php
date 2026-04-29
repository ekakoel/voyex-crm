<?php
$root = __DIR__ . '/../resources/views';
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
$c=0;
foreach($it as $f){
 if(!$f->isFile()||!str_ends_with($f->getFilename(),'.blade.php')) continue;
 $p=$f->getPathname(); $s=file_get_contents($p); $o=$s;
 $s=preg_replace_callback("/ui_phrase\(\s*'([^']+)'\s*,/",function($m)use(&$c){$k=$m[1];$nk=preg_replace('/^(common_|modules_[a-z0-9]+_)/','',$k); if($nk!==$k){$c++; return "ui_phrase('{$nk}',";} return $m[0];},$s);
 $s=preg_replace_callback('/ui_phrase\(\s*"([^"]+)"\s*,/',function($m)use(&$c){$k=$m[1];$nk=preg_replace('/^(common_|modules_[a-z0-9]+_)/','',$k); if($nk!==$k){$c++; return "ui_phrase(\"{$nk}\",";} return $m[0];},$s);
 if($s!==$o) file_put_contents($p,$s);
}
echo "replaced_with_params={$c}\n";
