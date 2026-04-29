<?php
$targets=['zh_Hant'=>'lang/zh_Hant/ui_core.php','zh_Hans'=>'lang/zh_Hans/ui_core.php'];
$rep=[
'zh_Hant'=>[
'Location on 地圖 (open map)'=>'地圖位置（開啟地圖）','open map'=>'開啟地圖','Timezone'=>'時區','Website'=>'網站','Contract Rate'=>'合約費率','Markup'=>'加價',
'KPI'=>'KPI','currencies'=>'幣別','employee data'=>'員工資料','tourist attractions'=>'景點','transport services'=>'交通服務',':size/頁'=>':size/頁',
'by :name'=>'由 :name 建立','Due Date'=>'到期日','Rate History'=>'費率歷史','Linked'=>'已連結','Contact Person'=>'聯絡人',':distance km'=>':distance 公里',
'City / Province'=>'城市 / 省份','City'=>'城市','Province'=>'省份','Latitude'=>'緯度','Longitude'=>'經度','Google Maps URL'=>'Google 地圖網址','Open map'=>'開啟地圖',
'Fast Boat'=>'快艇','Validate 報價單'=>'驗證報價單','My 報價單'=>'我的報價單','You have already approved this quotation.'=>'你已經核准過此報價單。',
'Activate this activity?'=>'要啟用此活動嗎？','Deactivate this activity?'=>'要停用此活動嗎？','Activate this airport?'=>'要啟用此機場嗎？','Deactivate this airport?'=>'要停用此機場嗎？',
'Pricing (IDR)'=>'定價（IDR）','Rate / Pax'=>'費率 / 人','Property'=>'屬性','No'=>'編號',
'Are you sure you want to delete this booking?'=>'你確定要刪除此預訂嗎？',
'Sign in'=>'登入','password'=>'密碼','gallery'=>'圖庫','thumbnail'=>'縮圖','image'=>'圖片','map資料。'=>'地圖資料。','coordinates'=>'座標',
'Fill airport code and name consistently.'=>'請一致填寫機場代碼與名稱。','Use Google Maps URL then click `Auto Fill` for accurate location.'=>'請使用 Google 地圖網址並點擊 `Auto Fill` 以取得正確位置。',
'Make sure `目的地`, coordinates, and status are correct before saving.'=>'儲存前請確認 `目的地`、座標與狀態正確。',
'Location changes will affect itinerary and map資料。'=>'位置變更會影響行程與地圖資料。',
'Use `Auto Fill` when updating the Google Maps link.'=>'更新 Google 地圖連結時請使用 `Auto Fill`。',
'Check airport status before saving changes.'=>'儲存變更前請確認機場狀態。',
],
'zh_Hans'=>[
'Location on 地图 (open map)'=>'地图位置（打开地图）','open map'=>'打开地图','Timezone'=>'时区','Website'=>'网站','Contract Rate'=>'合同费率','Markup'=>'加价',
'KPI'=>'KPI','currencies'=>'币别','employee data'=>'员工资料','tourist attractions'=>'景点','transport services'=>'交通服务',':size/页'=>':size/页',
'by :name'=>'由 :name 创建','Due Date'=>'到期日','Rate History'=>'费率历史','Linked'=>'已关联','Contact Person'=>'联系人',':distance km'=>':distance 公里',
'City / Province'=>'城市 / 省份','City'=>'城市','Province'=>'省份','Latitude'=>'纬度','Longitude'=>'经度','Google Maps URL'=>'Google 地图网址','Open map'=>'打开地图',
'Fast Boat'=>'快艇','Validate 报价单'=>'验证报价单','My 报价单'=>'我的报价单','You have already approved this quotation.'=>'你已经审批过此报价单。',
'Activate this activity?'=>'要启用此活动吗？','Deactivate this activity?'=>'要停用此活动吗？','Activate this airport?'=>'要启用此机场吗？','Deactivate this airport?'=>'要停用此机场吗？',
'Pricing (IDR)'=>'定价（IDR）','Rate / Pax'=>'费率 / 人','Property'=>'属性','No'=>'编号',
'Are you sure you want to delete this booking?'=>'你确定要删除此预订吗？',
'Sign in'=>'登录','password'=>'密码','gallery'=>'图库','thumbnail'=>'缩略图','image'=>'图片','map资料。'=>'地图资料。','coordinates'=>'坐标',
'Fill airport code and name consistently.'=>'请一致填写机场代码与名称。','Use Google Maps URL then click `Auto Fill` for accurate location.'=>'请使用 Google 地图网址并点击 `Auto Fill` 以获取准确位置。',
'Make sure `目的地`, coordinates, and status are correct before saving.'=>'保存前请确认 `目的地`、坐标与状态正确。',
'Location changes will affect itinerary and map资料。'=>'位置变更会影响行程与地图资料。',
'Use `Auto Fill` when updating the Google Maps link.'=>'更新 Google 地图链接时请使用 `Auto Fill`。',
'Check airport status before saving changes.'=>'保存变更前请确认机场状态。',
]
];
function exa(array $a){$o="<?php\n\nreturn [\n";foreach($a as $k=>$v){$k=str_replace(["\\","'"],["\\\\","\\'"],$k);if(is_string($v)){$v=str_replace(["\\","'"],["\\\\","\\'"],$v);$o.="    '{$k}' => '{$v}',\n";}else{$o.="    '{$k}' => ".var_export($v,true).",\n";}}return $o."];\n";}
foreach($targets as $lc=>$f){$arr=include $f;$c=0;foreach($arr as $k=>$v){if(!is_string($v)||$v==='')continue;$nv=str_replace(array_keys($rep[$lc]),array_values($rep[$lc]),$v);if($nv!==$v){$arr[$k]=$nv;$c++;}}file_put_contents($f,exa($arr));echo "$lc phrase3=$c\n";}
