<?php
function loadCommitArr(string $commit, string $path): array {
    $tmp = sys_get_temp_dir() . '/tmp_' . md5($commit.$path) . '.php';
    $content = shell_exec('git show ' . escapeshellarg($commit . ':' . $path));
    if (!is_string($content) || trim($content)==='') return [];
    file_put_contents($tmp, $content);
    $arr = include $tmp;
    @unlink($tmp);
    return is_array($arr) ? $arr : [];
}

$targetFiles = [
  'resources/views/layouts/master.blade.php',
  'resources/views/layouts/navigation.blade.php',
];
foreach (glob('resources/views/modules/customers/*.blade.php') as $f) $targetFiles[]=$f;
foreach (glob('resources/views/modules/inquiries/*.blade.php') as $f) $targetFiles[]=$f;
foreach (glob('resources/views/modules/itineraries/*.blade.php') as $f) $targetFiles[]=$f;

$used=[];
foreach($targetFiles as $f){
  if(!is_file($f)) continue;
  $c=file_get_contents($f);
  preg_match_all("/ui_phrase\\('([^']+)'/", $c, $m);
  foreach(($m[1]??[]) as $k) $used[$k]=true;
}

$en = include 'lang/en/ui_core.php';
$zhs = include 'lang/zh_Hans/ui_core.php';
$zht = include 'lang/zh_Hant/ui_core.php';

$oldEn = loadCommitArr('cfa85e1','lang/en/ui_core.php');
$oldHs = loadCommitArr('cfa85e1','lang/zh_Hans/ui_core.php');
$oldHt = loadCommitArr('cfa85e1','lang/zh_Hant/ui_core.php');

$mapHs=[]; $mapHt=[];
foreach($oldEn as $k=>$v){
  $phrase = trim((string)$v);
  if($phrase==='') continue;
  $mapHs[$phrase] = isset($oldHs[$k]) ? (string)$oldHs[$k] : ($mapHs[$phrase] ?? '');
  $mapHt[$phrase] = isset($oldHt[$k]) ? (string)$oldHt[$k] : ($mapHt[$phrase] ?? '');
}

$manualHs = [
  'Add Item' => '添加项目',
  'You can add new items (Attraction, Activity, Inter Island Transfer, or F&B) to this itinerary.' => '你可以向此行程新增项目（景点、活动、岛际接送或餐饮）。',
];
$manualHt = [
  'Add Item' => '新增項目',
  'You can add new items (Attraction, Activity, Inter Island Transfer, or F&B) to this itinerary.' => '你可以向此行程新增項目（景點、活動、島際接送或餐飲）。',
];

$patchedHs=0; $patchedHt=0;
foreach(array_keys($used) as $key){
  $ev = $en[$key] ?? null;
  if($ev===null) continue;

  $needHs = !array_key_exists($key,$zhs) || (string)$zhs[$key]===(string)$ev || trim((string)$zhs[$key])==='';
  $needHt = !array_key_exists($key,$zht) || (string)$zht[$key]===(string)$ev || trim((string)$zht[$key])==='';

  if($needHs){
    if(isset($manualHs[$key])){ $zhs[$key]=$manualHs[$key]; $patchedHs++; }
    elseif(isset($mapHs[$ev]) && trim($mapHs[$ev])!==''){ $zhs[$key]=$mapHs[$ev]; $patchedHs++; }
  }
  if($needHt){
    if(isset($manualHt[$key])){ $zht[$key]=$manualHt[$key]; $patchedHt++; }
    elseif(isset($mapHt[$ev]) && trim($mapHt[$ev])!==''){ $zht[$key]=$mapHt[$ev]; $patchedHt++; }
  }
}

ksort($zhs, SORT_NATURAL|SORT_FLAG_CASE);
ksort($zht, SORT_NATURAL|SORT_FLAG_CASE);
file_put_contents('lang/zh_Hans/ui_core.php', "<?php\n\nreturn ".var_export($zhs,true).";\n");
file_put_contents('lang/zh_Hant/ui_core.php', "<?php\n\nreturn ".var_export($zht,true).";\n");

echo "patched_hans={$patchedHs}\npatched_hant={$patchedHt}\n";
