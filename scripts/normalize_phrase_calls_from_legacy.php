<?php
$legacy = include __DIR__.'/../config/ui_legacy_map.php';
$roots=[__DIR__.'/../resources', __DIR__.'/../app'];
$changed=0;

$resolve = function(string $k) use ($legacy): ?string {
  if (isset($legacy[$k]) && is_string($legacy[$k])) return $legacy[$k];
  $cand = strtolower(trim(preg_replace('/\s+/', '_', $k) ?? $k));
  if (isset($legacy[$cand]) && is_string($legacy[$cand])) return $legacy[$cand];
  return null;
};

foreach($roots as $root){
 $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
 foreach($it as $f){
  if(!$f->isFile()) continue;
  $p=$f->getPathname();
  if(!preg_match('/\.(php|blade\.php)$/',$p)) continue;
  $s=file_get_contents($p); $o=$s;
  $s=preg_replace_callback("/ui_phrase\(\s*'([^']+)'\s*([,)])/", function($m) use($resolve,&$changed){
    $k=$m[1]; $mapped=$resolve($k); if(!$mapped || $mapped===$k) return $m[0];
    $changed++; $mapped=str_replace("'","\\'",$mapped); return "ui_phrase('{$mapped}'{$m[2]}";
  }, $s);
  $s=preg_replace_callback('/ui_phrase\(\s*"([^"]+)"\s*([,)])/', function($m) use($resolve,&$changed){
    $k=$m[1]; $mapped=$resolve($k); if(!$mapped || $mapped===$k) return $m[0];
    $changed++; $mapped=str_replace('"','\\"',$mapped); return 'ui_phrase("'.$mapped.'"'.$m[2];
  }, $s);
  if($s!==$o) file_put_contents($p,$s);
 }
}
echo "replacements={$changed}\n";
