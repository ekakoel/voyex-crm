<?php
$base = getcwd();
$en = include $base.'/lang/en/ui_core.php';
$targets = ['lang/zh_Hant/ui_core.php','lang/zh_Hans/ui_core.php'];

function hasAsciiLetters(string $s): bool { return preg_match('/[A-Za-z]/', $s) === 1; }
function isLocalized(string $s): bool { return $s !== '' && !hasAsciiLetters($s); }

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

foreach ($targets as $rel) {
  $path = $base.'/'.$rel;
  $loc = include $path;
  $changed = 0;

  // Build localized base map by key name
  $baseMap = [];
  foreach ($loc as $k => $v) {
    if (!is_string($v) || !isLocalized($v)) continue;
    $baseMap[$k] = $v;
  }

  foreach ($loc as $k => $v) {
    if (!isset($en[$k]) || !is_string($v) || !is_string($en[$k])) continue;
    if ($v !== $en[$k]) continue; // only untouched english copies

    $candidate = null;

    // common_xxx -> xxx
    if (strpos($k, 'common_') === 0) {
      $tail = substr($k, 7);
      if (isset($baseMap[$tail])) $candidate = $baseMap[$tail];
    }

    // entities_xxx -> xxx or singular
    if ($candidate === null && strpos($k, 'entities_') === 0) {
      $tail = substr($k, 9);
      $try = [$tail, rtrim($tail, 's')];
      foreach ($try as $t) {
        if (isset($baseMap[$t])) { $candidate = $baseMap[$t]; break; }
      }
    }

    // modules_<mod>_<tail> ; try tail direct
    if ($candidate === null && preg_match('/^modules_[^_]+_(.+)$/', $k, $m)) {
      $tail = $m[1];
      $try = [$tail, rtrim($tail, 's')];
      foreach ($try as $t) {
        if (isset($baseMap[$t])) { $candidate = $baseMap[$t]; break; }
      }
      if ($candidate === null && isset($baseMap['common_'.$tail])) $candidate = $baseMap['common_'.$tail];
    }

    // shared_phrases_xxx -> xxx when exact key exists
    if ($candidate === null && strpos($k, 'shared_phrases_') === 0) {
      $tail = substr($k, 15);
      if (isset($baseMap[$tail])) $candidate = $baseMap[$tail];
    }

    if ($candidate !== null && $candidate !== $v) {
      $loc[$k] = $candidate;
      $changed++;
    }
  }

  file_put_contents($path, exportArray($loc));
  echo "$rel base-map patched=$changed\n";
}
