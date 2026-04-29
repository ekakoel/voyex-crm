<?php
$root = __DIR__ . '/../resources/views';
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
$changed = [];
foreach ($it as $f) {
    if (!$f->isFile() || $f->getExtension() !== 'php') continue;
    $p = $f->getPathname();
    if (!str_ends_with($p, '.blade.php')) continue;
    $src = file_get_contents($p);
    $orig = $src;

    $src = preg_replace_callback("/\{\{\s*__\(\s*'([^']+)'\s*\)\s*\}\}/", function($m){
        $k = $m[1];
        if (str_contains($k,'.') || str_contains($k,'::') || preg_match('/[:$\\[\\]\\(\\)]/', $k)) return $m[0];
        return "{{ ui_phrase('".$k."') }}";
    }, $src);

    $src = preg_replace_callback('/\{\{\s*__\(\s*"([^"]+)"\s*\)\s*\}\}/', function($m){
        $k = $m[1];
        if (str_contains($k,'.') || str_contains($k,'::') || preg_match('/[:$\\[\\]\\(\\)]/', $k)) return $m[0];
        return "{{ ui_phrase('".$k."') }}";
    }, $src);

    $src = preg_replace_callback("/__\(\s*'([^']+)'\s*\)/", function($m){
        $k = $m[1];
        if (str_contains($k,'.') || str_contains($k,'::') || preg_match('/[$\\[]/', $k)) return $m[0];
        return "ui_phrase('".$k."')";
    }, $src);

    $src = preg_replace_callback('/__\(\s*"([^"]+)"\s*\)/', function($m){
        $k = $m[1];
        if (str_contains($k,'.') || str_contains($k,'::') || preg_match('/[$\\[]/', $k)) return $m[0];
        return "ui_phrase('".$k."')";
    }, $src);

    if ($src !== $orig) {
        file_put_contents($p, $src);
        $changed[] = str_replace('\\','/',$p);
    }
}
echo "changed_files=".count($changed)."\n";
foreach ($changed as $c) echo $c."\n";
