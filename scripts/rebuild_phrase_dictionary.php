<?php
$enOld = include __DIR__ . '/../lang/en/ui_core.php';
$hOld = include __DIR__ . '/../lang/zh_Hant/ui_core.php';
$sOld = include __DIR__ . '/../lang/zh_Hans/ui_core.php';

$coreKey = static function (string $value): string {
    $key = trim($value);
    $key = str_replace(['-', ' '], '_', $key);
    $key = trim($key, '.');
    if (str_starts_with($key, 'ui.')) {
        $key = substr($key, 3);
    }
    $key = preg_replace('/([a-z])([A-Z])/', '$1_$2', $key) ?? $key;
    $key = strtolower($key);
    $key = str_replace('.', '_', $key);
    $key = preg_replace('/[^a-z0-9_]+/', '_', $key) ?? $key;
    $key = preg_replace('/_+/', '_', $key) ?? $key;
    return trim($key, '_');
};

$newEn = [];
$newH = [];
$newS = [];
$legacy = [];

$makePhrase = static function (string $text, string $fallback): string {
    $phrase = trim(preg_replace('/\s+/', ' ', $text) ?? '');
    if ($phrase === '') {
        $phrase = trim(preg_replace('/[_\-]+/', ' ', $fallback) ?? $fallback);
    }
    return $phrase;
};

foreach ($enOld as $oldKey => $enVal) {
    $oldKey = (string) $oldKey;
    $enVal = (string) $enVal;
    $phrase = $makePhrase($enVal, $oldKey);

    if (! array_key_exists($phrase, $newEn)) {
        $newEn[$phrase] = $phrase;
        $newH[$phrase] = array_key_exists($oldKey, $hOld) ? (string) $hOld[$oldKey] : $phrase;
        $newS[$phrase] = array_key_exists($oldKey, $sOld) ? (string) $sOld[$oldKey] : $phrase;
    }

    $legacy[$oldKey] = $phrase;
    $legacy[$coreKey($oldKey)] = $phrase;
    $legacy[$coreKey($phrase)] = $phrase;
}

ksort($newEn, SORT_NATURAL | SORT_FLAG_CASE);
ksort($newH, SORT_NATURAL | SORT_FLAG_CASE);
ksort($newS, SORT_NATURAL | SORT_FLAG_CASE);
ksort($legacy, SORT_NATURAL | SORT_FLAG_CASE);

$write = static function (string $path, array $arr): void {
    $content = "<?php\n\nreturn " . var_export($arr, true) . ";\n";
    file_put_contents($path, $content);
};

$write(__DIR__ . '/../lang/en/ui_core.php', $newEn);
$write(__DIR__ . '/../lang/zh_Hant/ui_core.php', $newH);
$write(__DIR__ . '/../lang/zh_Hans/ui_core.php', $newS);
$write(__DIR__ . '/../config/ui_legacy_map.php', $legacy);

echo 'new_en=' . count($newEn) . PHP_EOL;
echo 'new_zh_Hant=' . count($newH) . PHP_EOL;
echo 'new_zh_Hans=' . count($newS) . PHP_EOL;
echo 'legacy=' . count($legacy) . PHP_EOL;
