<?php
$legacy = include __DIR__.'/../config/ui_legacy_map.php';
if (!is_array($legacy)) { echo "legacy map invalid\n"; exit(1);} 

$roots = [__DIR__.'/../resources', __DIR__.'/../app'];
$changed=0; $files=0;
foreach ($roots as $root) {
  $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
  foreach ($it as $f) {
    if (!$f->isFile()) continue;
    $name = $f->getFilename();
    $path = $f->getPathname();
    if (!preg_match('/\.(php|blade\.php)$/', $path)) continue;
    $src = file_get_contents($path);
    $orig = $src;

    $src = preg_replace_callback("/ui_phrase\(\s*'([^']+)'\s*([,)])/", function($m) use($legacy){
      $k=$m[1]; $end=$m[2];
      if (!isset($legacy[$k]) || !is_string($legacy[$k])) return $m[0];
      $mapped = $legacy[$k];
      $mapped = str_replace("'", "\\'", $mapped);
      return "ui_phrase('{$mapped}'{$end}";
    }, $src);

    $src = preg_replace_callback('/ui_phrase\(\s*"([^"]+)"\s*([,)])/', function($m) use($legacy){
      $k=$m[1]; $end=$m[2];
      if (!isset($legacy[$k]) || !is_string($legacy[$k])) return $m[0];
      $mapped = $legacy[$k];
      $mapped = str_replace('"', '\\"', $mapped);
      return 'ui_phrase("'.$mapped.'"'.$end;
    }, $src);

    if ($src !== $orig) {
      file_put_contents($path,$src);
      $changed++; 
      echo str_replace('\\','/',$path)."\n";
    }
    $files++;
  }
}
echo "files_scanned={$files}\nchanged={$changed}\n";
