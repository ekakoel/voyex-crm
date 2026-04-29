<?php
$root = __DIR__ . '/../resources/views';
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
$count = 0;
foreach ($it as $f) {
  if (!$f->isFile() || !str_ends_with($f->getFilename(), '.blade.php')) continue;
  $p = $f->getPathname();
  $s = file_get_contents($p);
  $o = $s;
  $s = preg_replace_callback("/ui_phrase\(\s*'([^']+)'\s*\)/", function($m) use (&$count){
      $k = $m[1];
      $nk = $k;
      $nk = preg_replace('/^(common_|modules_[a-z0-9]+_)/', '', $nk);
      if ($nk !== $k) { $count++; return "ui_phrase('{$nk}')"; }
      return $m[0];
  }, $s);
  $s = preg_replace_callback('/ui_phrase\(\s*"([^"]+)"\s*\)/', function($m) use (&$count){
      $k = $m[1];
      $nk = $k;
      $nk = preg_replace('/^(common_|modules_[a-z0-9]+_)/', '', $nk);
      if ($nk !== $k) { $count++; return "ui_phrase(\"{$nk}\")"; }
      return $m[0];
  }, $s);
  if ($s !== $o) file_put_contents($p, $s);
}
echo "replaced={$count}\n";
