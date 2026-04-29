<?php
$en = include __DIR__ . '/../lang/en/ui_core.php';
$h = include __DIR__ . '/../lang/zh_Hant/ui_core.php';
$s = include __DIR__ . '/../lang/zh_Hans/ui_core.php';
echo 'en=' . count($en) . PHP_EOL;
echo 'zh_Hant=' . count($h) . PHP_EOL;
echo 'zh_Hans=' . count($s) . PHP_EOL;

echo "\n-- en sample --\n";
$i=0;
foreach ($en as $k=>$v) { echo $k.' => '.$v.PHP_EOL; if(++$i>=60) break; }

echo "\n-- zh_Hant sample --\n";
$i=0;
foreach ($h as $k=>$v) { echo $k.' => '.$v.PHP_EOL; if(++$i>=60) break; }
