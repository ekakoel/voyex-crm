<?php
$map = [
  'AC/Driver' => ['h'=>'冷氣/司機','s'=>'冷气/司机'],
  'API key is not configured yet. Please set GOOGLE_MAPS_PLACES_API_KEY in .env.' => ['h'=>'API 金鑰尚未設定。請先在 .env 設定 GOOGLE_MAPS_PLACES_API_KEY。','s'=>'API 密钥尚未设置。请先在 .env 设置 GOOGLE_MAPS_PLACES_API_KEY。'],
  'Arrival Google Maps URL' => ['h'=>'抵達 Google 地圖 URL','s'=>'抵达 Google 地图 URL'],
  'Avg SLA (Approval -> Booking)' => ['h'=>'平均 SLA（核准 -> 預訂）','s'=>'平均 SLA（批准 -> 预订）'],
  'Coordinates are not valid yet. Fill from Map URL or click on the map.' => ['h'=>'座標目前無效。請從地圖 URL 填入，或直接點擊地圖。','s'=>'坐标目前无效。请从地图 URL 填入，或直接点击地图。'],
  'CSV File' => ['h'=>'CSV 檔案','s'=>'CSV 文件'],
  'CSV is empty or has an invalid format.' => ['h'=>'CSV 為空或格式無效。','s'=>'CSV 为空或格式无效。'],
  'Departure Google Maps URL' => ['h'=>'出發 Google 地圖 URL','s'=>'出发 Google 地图 URL'],
  'Download the CSV template for the standard format.' => ['h'=>'下載標準格式的 CSV 範本。','s'=>'下载标准格式的 CSV 模板。'],
  'Failed to read CSV file.' => ['h'=>'讀取 CSV 檔案失敗。','s'=>'读取 CSV 文件失败。'],
  'Fill name, code, symbol, and IDR rate. Ensure the rate is valid before saving.' => ['h'=>'請填寫名稱、代碼、符號與 IDR 匯率，並在儲存前確認匯率有效。','s'=>'请填写名称、代码、符号与 IDR 汇率，并在保存前确认汇率有效。'],
  'Google Maps URL' => ['h'=>'Google 地圖 URL','s'=>'Google 地图 URL'],
  'Google Places API key is not configured. Please set GOOGLE_MAPS_PLACES_API_KEY first.' => ['h'=>'Google Places API 金鑰尚未設定。請先設定 GOOGLE_MAPS_PLACES_API_KEY。','s'=>'Google Places API 密钥尚未设置。请先设置 GOOGLE_MAPS_PLACES_API_KEY。'],
  'IDR Rate' => ['h'=>'IDR 匯率','s'=>'IDR 汇率'],
  'Import CSV' => ['h'=>'匯入 CSV','s'=>'导入 CSV'],
  'Import failed. Please check the CSV data.' => ['h'=>'匯入失敗，請檢查 CSV 資料。','s'=>'导入失败，请检查 CSV 数据。'],
  'KPI' => ['h'=>'KPI','s'=>'KPI'],
  'Map URL' => ['h'=>'地圖 URL','s'=>'地图 URL'],
  'No access to master data KPI.' => ['h'=>'無法存取主資料 KPI。','s'=>'无法访问主数据 KPI。'],
  'No access to operational KPI.' => ['h'=>'無法存取營運 KPI。','s'=>'无法访问运营 KPI。'],
  'No access to system management KPI.' => ['h'=>'無法存取系統管理 KPI。','s'=>'无法访问系统管理 KPI。'],
  'Non-AC' => ['h'=>'無冷氣','s'=>'无冷气'],
  'Paste a Google Maps URL containing coordinates, then click Auto Fill Coordinates.' => ['h'=>'貼上包含座標的 Google 地圖 URL，然後點擊自動填入座標。','s'=>'粘贴包含坐标的 Google 地图 URL，然后点击自动填入坐标。'],
  'Pricing (IDR)' => ['h'=>'價格（IDR）','s'=>'价格（IDR）'],
  'Rate to IDR' => ['h'=>'換算為 IDR 匯率','s'=>'换算为 IDR 汇率'],
  'Sign in to continue managing your travel CRM workflow in one secure place.' => ['h'=>'登入後可在單一安全平台持續管理您的旅遊 CRM 流程。','s'=>'登录后可在单一安全平台持续管理您的旅游 CRM 流程。'],
  'Smart Travel CRM Platform' => ['h'=>'智慧旅遊 CRM 平台','s'=>'智慧旅游 CRM 平台'],
  'Unable to extract coordinates from this Google Maps URL. Please verify the URL format or fill coordinates manually.' => ['h'=>'無法從此 Google 地圖 URL 擷取座標。請確認 URL 格式，或手動填入座標。','s'=>'无法从此 Google 地图 URL 提取坐标。请确认 URL 格式，或手动填入坐标。'],
  'Upload CSV to import customers.' => ['h'=>'上傳 CSV 以匯入客戶。','s'=>'上传 CSV 以导入客户。'],
  'Use Google Maps URL then click `Auto Fill` for accurate location.' => ['h'=>'使用 Google 地圖 URL，並點擊 `Auto Fill` 以取得準確位置。','s'=>'使用 Google 地图 URL，并点击 `Auto Fill` 以获取准确位置。'],
  'View Itinerary PDF' => ['h'=>'查看行程 PDF','s'=>'查看行程 PDF'],
  'Download PDF' => ['h'=>'下載 PDF','s'=>'下载 PDF'],
  'Preview PDF' => ['h'=>'預覽 PDF','s'=>'预览 PDF'],
];

$h = include __DIR__.'/../lang/zh_Hant/ui_core.php';
$s = include __DIR__.'/../lang/zh_Hans/ui_core.php';
foreach($map as $k=>$v){
  if(array_key_exists($k,$h)) $h[$k]=$v['h'];
  if(array_key_exists($k,$s)) $s[$k]=$v['s'];
}
ksort($h,SORT_NATURAL|SORT_FLAG_CASE); ksort($s,SORT_NATURAL|SORT_FLAG_CASE);
file_put_contents(__DIR__.'/../lang/zh_Hant/ui_core.php',"<?php\n\nreturn ".var_export($h,true).";\n");
file_put_contents(__DIR__.'/../lang/zh_Hans/ui_core.php',"<?php\n\nreturn ".var_export($s,true).";\n");
echo "patched\n";
