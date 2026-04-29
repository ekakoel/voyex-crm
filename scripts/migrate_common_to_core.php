<?php
$files = [
  'lang/en/ui_core.php',
  'lang/zh_Hant/ui_core.php',
  'lang/zh_Hans/ui_core.php',
];

function exportArray(array $arr): string {
  $out = "<?php\n\nreturn [\n";
  foreach ($arr as $k => $v) {
    $ks = str_replace(["\\", "'"], ["\\\\", "\\'"], (string)$k);
    if (is_string($v)) {
      $vs = str_replace(["\\", "'"], ["\\\\", "\\'"], $v);
      $out .= "    '{$ks}' => '{$vs}',\n";
    } else {
      $out .= "    '{$ks}' => " . var_export($v, true) . ",\n";
    }
  }
  $out .= "];\n";
  return $out;
}

foreach ($files as $f) {
  $arr = include $f;
  $addedBase = 0;
  $removedCommon = 0;

  // First pass: ensure base key exists for every common_* key.
  foreach ($arr as $k => $v) {
    if (!str_starts_with($k, 'common_')) continue;
    $base = substr($k, 7);
    if ($base === '') continue;
    if (!array_key_exists($base, $arr)) {
      $arr[$base] = $v;
      $addedBase++;
    }
  }

  // Second pass: remove common_* keys.
  foreach (array_keys($arr) as $k) {
    if (str_starts_with($k, 'common_')) {
      unset($arr[$k]);
      $removedCommon++;
    }
  }

  file_put_contents($f, exportArray($arr));
  echo "$f added_base={$addedBase} removed_common={$removedCommon}\n";
}
