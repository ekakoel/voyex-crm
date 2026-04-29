<?php
$base = getcwd();
$en = include $base.'/lang/en/ui_core.php';
$targets = [
  'lang/zh_Hant/ui_core.php',
  'lang/zh_Hans/ui_core.php',
];

function hasAsciiLetters(string $s): bool {
  return preg_match('/[A-Za-z]/', $s) === 1;
}

function isTokenLike(string $s): bool {
  return preg_match('/^[a-z0-9_\.:-]+$/', $s) === 1;
}

function exportArray(array $arr): string {
  $out = "<?php\n\nreturn [\n";
  foreach ($arr as $k => $v) {
    $ks = str_replace(["\\", "'"], ["\\\\", "\\'"], (string) $k);
    if (is_string($v)) {
      $vs = str_replace(["\\", "'"], ["\\\\", "\\'"], $v);
      $out .= "    '{$ks}' => '{$vs}',\n";
    } elseif (is_int($v) || is_float($v)) {
      $out .= "    '{$ks}' => {$v},\n";
    } elseif (is_bool($v)) {
      $out .= "    '{$ks}' => " . ($v ? 'true' : 'false') . ",\n";
    } elseif ($v === null) {
      $out .= "    '{$ks}' => null,\n";
    } else {
      $out .= "    '{$ks}' => " . json_encode($v, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) . ",\n";
    }
  }
  $out .= "];\n";
  return $out;
}

foreach ($targets as $rel) {
  $path = $base.'/'.$rel;
  $locale = include $path;

  // Build phrase map from EN phrase -> localized phrase from already-translated entries.
  $phraseMap = [];
  foreach ($locale as $k => $v) {
    if (!isset($en[$k]) || !is_string($en[$k]) || !is_string($v)) continue;
    $enVal = $en[$k];
    $locVal = $v;
    if ($locVal === '' || $locVal === $enVal) continue;
    if (hasAsciiLetters($locVal)) continue; // keep only true localized (CJK/non-ascii) for safe map
    if (isTokenLike($locVal)) continue;
    if (!isset($phraseMap[$enVal])) {
      $phraseMap[$enVal] = $locVal;
    }
  }

  $changed = 0;
  foreach ($locale as $k => $v) {
    if (!isset($en[$k]) || !is_string($en[$k]) || !is_string($v)) continue;
    if ($v === '' || $v !== $en[$k]) continue; // target only pure english-copied values
    $enVal = $en[$k];
    if (isset($phraseMap[$enVal])) {
      $locale[$k] = $phraseMap[$enVal];
      $changed++;
    }
  }

  file_put_contents($path, exportArray($locale));
  echo $rel." phrase-map patched=".$changed."\n";
}
