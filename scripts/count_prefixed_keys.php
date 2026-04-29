<?php
foreach (['en','zh_Hant','zh_Hans'] as $loc) {
  $a = include __DIR__ . '/../lang/' . $loc . '/ui_core.php';
  $pref = 0;
  foreach (array_keys($a) as $k) {
    if (str_starts_with($k,'common_') || str_starts_with($k,'modules_') || str_starts_with($k,'shared_phrases_')) $pref++;
  }
  echo $loc . ':prefixed=' . $pref . PHP_EOL;
}
