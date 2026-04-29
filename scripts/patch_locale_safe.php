<?php
$base = getcwd();
$enPath = $base . '/lang/en/ui_core.php';
$hantPath = $base . '/lang/zh_Hant/ui_core.php';
$hansPath = $base . '/lang/zh_Hans/ui_core.php';
$en = include $enPath;
$hant = include $hantPath;
$hans = include $hansPath;

function isEnglishLike(string $v): bool {
    return preg_match('/[A-Za-z]/', $v) === 1;
}

function buildTermMap(array $locale): array {
    $map = [];
    foreach ($locale as $k => $v) {
        if (!is_string($v) || $v === '') continue;
        if (isEnglishLike($v)) continue;
        $map[$k] = $v;
    }
    return $map;
}

function patchLocale(array $en, array $locale): array {
    $termMap = buildTermMap($locale);
    $patched = $locale;
    $changes = 0;

    foreach ($locale as $k => $v) {
        if (!is_string($v) || $v === '' || !isEnglishLike($v)) continue;
        $enVal = $en[$k] ?? null;
        if (!is_string($enVal) || $enVal !== $v) continue;

        $candidate = null;

        // common_xxx -> xxx
        if (strpos($k, 'common_') === 0) {
            $base = substr($k, 7);
            if (isset($termMap[$base])) $candidate = $termMap[$base];
        }

        // entities_xxx -> xxx/plural variants
        if ($candidate === null && strpos($k, 'entities_') === 0) {
            $base = substr($k, 9);
            $variants = [$base, rtrim($base, 's')];
            foreach ($variants as $vk) {
                if (isset($termMap[$vk])) { $candidate = $termMap[$vk]; break; }
            }
        }

        // modules_xxx_page_title -> xxx or plural fallback
        if ($candidate === null && preg_match('/^modules_([^_]+)_page_title$/', $k, $m)) {
            $base = $m[1];
            $variants = [$base, rtrim($base, 's')];
            foreach ($variants as $vk) {
                if (isset($termMap[$vk])) { $candidate = $termMap[$vk]; break; }
            }
        }

        // modules_xxx_room etc -> room
        if ($candidate === null && preg_match('/_(room|hotel|hotels|edit|preview_pdf|download_pdf|generate_quotation|schedule_by_day|display_by_day|itinerary_map)$/', $k, $m)) {
            $tail = $m[1];
            $tailToKey = [
                'room' => 'room',
                'hotel' => 'hotel',
                'hotels' => 'hotels',
                'edit' => 'edit',
                'preview_pdf' => 'preview_pdf',
                'download_pdf' => 'download_pdf',
                'generate_quotation' => 'generate_quotation',
                'schedule_by_day' => 'schedule_by_day',
                'display_by_day' => 'display_by_day',
                'itinerary_map' => 'itinerary_map',
            ];
            $tk = $tailToKey[$tail] ?? null;
            if ($tk && isset($termMap[$tk])) $candidate = $termMap[$tk];
            if ($candidate === null && $tk && isset($termMap['common_' . $tk])) $candidate = $termMap['common_' . $tk];
        }

        if ($candidate !== null && $candidate !== $v) {
            $patched[$k] = $candidate;
            $changes++;
        }
    }

    return [$patched, $changes];
}

function exportArray(array $arr): string {
    $out = "<?php\n\nreturn [\n";
    foreach ($arr as $k => $v) {
        $ks = str_replace(["\\", "'"], ["\\\\", "\\'"], (string)$k);
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
            $json = json_encode($v, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
            $out .= "    '{$ks}' => {$json},\n";
        }
    }
    $out .= "];\n";
    return $out;
}

[$hantPatched, $c1] = patchLocale($en, $hant);
[$hansPatched, $c2] = patchLocale($en, $hans);

file_put_contents($hantPath, exportArray($hantPatched));
file_put_contents($hansPath, exportArray($hansPatched));

echo "patched zh_Hant={$c1}\n";
echo "patched zh_Hans={$c2}\n";
