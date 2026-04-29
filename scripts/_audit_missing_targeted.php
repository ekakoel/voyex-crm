<?php
$en = include 'lang/en/ui_core.php';
$zhs = include 'lang/zh_Hans/ui_core.php';
$zht = include 'lang/zh_Hant/ui_core.php';
foreach ($en as $k => $v) {
    $lk = strtolower($k);
    $wanted = str_contains($lk, 'customer')
        || str_contains($lk, 'inquiry')
        || str_contains($lk, 'import')
        || str_contains($lk, 'sidebar')
        || str_contains($lk, 'header')
        || str_contains($lk, 'page title')
        || str_contains($lk, 'page subtitle')
        || $lk === 'search'
        || $lk === 'action'
        || str_contains($lk, 'browse and manage data');
    if (! $wanted) continue;
    if (! array_key_exists($k, $zhs) || ! array_key_exists($k, $zht)) {
        echo $k, PHP_EOL;
    }
}
