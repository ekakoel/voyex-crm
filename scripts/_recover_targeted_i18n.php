<?php
$en = include __DIR__.'/../lang/en/ui_core.php';
$h = include __DIR__.'/../lang/zh_Hant/ui_core.php';
$s = include __DIR__.'/../lang/zh_Hans/ui_core.php';

$wanted = function(string $k): bool {
  $lk = strtolower($k);
  return str_contains($lk,'customer') || str_contains($lk,'inquiry') || str_contains($lk,'import') ||
         str_contains($lk,'sidebar') || str_contains($lk,'header') || str_contains($lk,'page title') ||
         str_contains($lk,'page subtitle') || $lk==='search' || $lk==='action' ||
         str_contains($lk,'browse and manage data');
};

$indexByLower = function(array $arr): array {
  $idx=[];
  foreach($arr as $k=>$v){ $idx[strtolower($k)] = $v; }
  return $idx;
};
$hIdx=$indexByLower($h);
$sIdx=$indexByLower($s);

$manualH = [
  'Browse and manage data.' => '瀏覽並管理資料。',
  'page title' => '總覽',
  'page subtitle' => '瀏覽並管理資料。',
  'create page title' => '建立',
  'create page subtitle' => '建立新資料。',
  'edit page title' => '編輯',
  'edit page subtitle' => '更新既有資料。',
  'show page title' => '詳情',
  'show page subtitle' => '檢視完整詳細資訊。',
  'Search' => '搜尋',
  'search' => '搜尋',
  'action' => '操作',
  'Import Customers' => '匯入客戶',
  'Customer Import Preview' => '客戶匯入預覽',
  'Upload and import customer data.' => '上傳並匯入客戶資料。',
  'Review customer import data before submit.' => '提交前檢視客戶匯入資料。',
  'Manage customer records.' => '管理客戶資料。',
  'Manage inquiry records.' => '管理詢問資料。',
  'Create Inquiry' => '建立詢問',
  'Inquiry Detail' => '詢問詳情',
];
$manualS = [
  'Browse and manage data.' => '浏览并管理数据。',
  'page title' => '总览',
  'page subtitle' => '浏览并管理数据。',
  'create page title' => '创建',
  'create page subtitle' => '创建新数据。',
  'edit page title' => '编辑',
  'edit page subtitle' => '更新现有数据。',
  'show page title' => '详情',
  'show page subtitle' => '查看完整详细信息。',
  'Search' => '搜索',
  'search' => '搜索',
  'action' => '操作',
  'Import Customers' => '导入客户',
  'Customer Import Preview' => '客户导入预览',
  'Upload and import customer data.' => '上传并导入客户数据。',
  'Review customer import data before submit.' => '提交前检查客户导入数据。',
  'Manage customer records.' => '管理客户数据。',
  'Manage inquiry records.' => '管理询问数据。',
  'Create Inquiry' => '创建询问',
  'Inquiry Detail' => '询问详情',
];

$fill = function(array &$dst, array $idx, array $manual) use($en,$wanted){
  $added=0;
  foreach($en as $k=>$v){
    if(!$wanted($k)) continue;
    if(array_key_exists($k,$dst)) continue;
    $lk=strtolower($k);
    if(isset($manual[$k])){ $dst[$k]=$manual[$k]; $added++; continue; }
    if(isset($idx[$lk])){ $dst[$k]=$idx[$lk]; $added++; continue; }
    // fallback from common neighbor keys
    if(isset($dst['Customer']) && stripos($k,'Customer')!==false && trim($k)==='Customer'){ continue; }
    // safe fallback to english to avoid raw-key misses
    $dst[$k]=(string)$v;
    $added++;
  }
  return $added;
};

$ah=$fill($h,$hIdx,$manualH);
$as=$fill($s,$sIdx,$manualS);

$write = function(string $path, array $arr){
  ksort($arr, SORT_NATURAL|SORT_FLAG_CASE);
  $code = "<?php\n\nreturn ".var_export($arr, true).";\n";
  file_put_contents($path, $code);
};
$write(__DIR__.'/../lang/zh_Hant/ui_core.php',$h);
$write(__DIR__.'/../lang/zh_Hans/ui_core.php',$s);

echo "added_h=$ah\nadded_s=$as\n";
