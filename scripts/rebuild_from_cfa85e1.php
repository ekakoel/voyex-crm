<?php
function loadFromCommit(string $commit, string $path): array {
    $tmp = sys_get_temp_dir() . '/dict_' . md5($commit.$path) . '.php';
    $cmd = 'git show ' . escapeshellarg($commit . ':' . $path);
    $content = shell_exec($cmd);
    if (!is_string($content) || trim($content) === '') {
        throw new RuntimeException('Failed loading ' . $commit . ':' . $path);
    }
    file_put_contents($tmp, $content);
    $arr = include $tmp;
    @unlink($tmp);
    if (!is_array($arr)) throw new RuntimeException('Invalid array for '.$path);
    return $arr;
}

$commit = 'cfa85e1';
$oldEn = loadFromCommit($commit, 'lang/en/ui_core.php');
$oldH = loadFromCommit($commit, 'lang/zh_Hant/ui_core.php');
$oldS = loadFromCommit($commit, 'lang/zh_Hans/ui_core.php');

$newEn = [];
$newH = [];
$newS = [];
$legacy = [];

$coreKey = static function (string $value): string {
    $key = trim($value);
    $key = str_replace(['-', ' '], '_', $key);
    $key = trim($key, '.');
    if (str_starts_with($key, 'ui.')) $key = substr($key, 3);
    $key = preg_replace('/([a-z])([A-Z])/', '$1_$2', $key) ?? $key;
    $key = strtolower(str_replace('.', '_', $key));
    $key = preg_replace('/[^a-z0-9_]+/', '_', $key) ?? $key;
    $key = preg_replace('/_+/', '_', $key) ?? $key;
    return trim($key, '_');
};

foreach ($oldEn as $k => $enVal) {
    $k = (string)$k;
    $phrase = trim((string)$enVal);
    if ($phrase === '') {
        $phrase = trim(preg_replace('/[_\-]+/', ' ', $k) ?? $k);
    }
    if (!array_key_exists($phrase, $newEn)) {
        $newEn[$phrase] = $phrase;
        $newH[$phrase] = array_key_exists($k, $oldH) ? (string)$oldH[$k] : '';
        $newS[$phrase] = array_key_exists($k, $oldS) ? (string)$oldS[$k] : '';
    }

    $legacy[$k] = $phrase;
    $legacy[$coreKey($k)] = $phrase;
    $legacy[$coreKey($phrase)] = $phrase;
}

ksort($newEn, SORT_NATURAL|SORT_FLAG_CASE);
ksort($newH, SORT_NATURAL|SORT_FLAG_CASE);
ksort($newS, SORT_NATURAL|SORT_FLAG_CASE);
ksort($legacy, SORT_NATURAL|SORT_FLAG_CASE);

$write = function(string $path, array $arr){
    file_put_contents($path, "<?php\n\nreturn " . var_export($arr, true) . ";\n");
};

$write(__DIR__.'/../lang/en/ui_core.php', $newEn);
$write(__DIR__.'/../lang/zh_Hant/ui_core.php', $newH);
$write(__DIR__.'/../lang/zh_Hans/ui_core.php', $newS);
$write(__DIR__.'/../config/ui_legacy_map.php', $legacy);

echo 'en=' . count($newEn) . PHP_EOL;
echo 'h=' . count($newH) . PHP_EOL;
echo 's=' . count($newS) . PHP_EOL;
$emptyH = 0; $emptyS = 0;
foreach ($newEn as $p => $_) {
  if (trim((string)$newH[$p]) === '') $emptyH++;
  if (trim((string)$newS[$p]) === '') $emptyS++;
}
echo 'emptyH=' . $emptyH . PHP_EOL;
echo 'emptyS=' . $emptyS . PHP_EOL;
