<?php
$roots=[__DIR__.'/../resources',__DIR__.'/../app'];
$changed=0;
$normalize=function(string $k): string {
  if (preg_match('/^__.*__$/',$k)) return $k;
  $k = str_replace('_',' ',$k);
  $k = preg_replace('/\s+/',' ',$k) ?? $k;
  return trim($k);
};
foreach($roots as $root){
 $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
 foreach($it as $f){
  if(!$f->isFile()) continue;
  $p=$f->getPathname();
  if(!preg_match('/\.(php|blade\.php)$/',$p)) continue;
  $s=file_get_contents($p); $o=$s;
  $s=preg_replace_callback("/ui_phrase\(\s*'([^']*_[^']*)'\s*([,)])/", function($m) use($normalize){$k=$m[1];$n=$normalize($k);$n=str_replace("'","\\'",$n);return "ui_phrase('{$n}'{$m[2]}";}, $s);
  $s=preg_replace_callback('/ui_phrase\(\s*"([^"\n]*_[^"\n]*)"\s*([,)])/', function($m) use($normalize){$k=$m[1];$n=$normalize($k);$n=str_replace('"','\\"',$n);return 'ui_phrase("'.$n.'"'.$m[2];}, $s);
  $s=preg_replace_callback("/ui_phrase\(\s*'([^']*_[^']*)'\s*\)/", function($m) use($normalize){$k=$m[1];$n=$normalize($k);$n=str_replace("'","\\'",$n);return "ui_phrase('{$n}')";}, $s);
  if($s!==$o){file_put_contents($p,$s);$changed++;}
 }
}
echo "changed={$changed}\n";
