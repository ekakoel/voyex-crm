<?php
$en=include 'lang/en/ui_core.php';
$hs=include 'lang/zh_Hans/ui_core.php';
$ht=include 'lang/zh_Hant/ui_core.php';
$targets=file('scripts/_audit_used_keys_missing_zh.php'); // not used

$collectMissing = function(array $locale) use($en){
  $files=['resources/views/layouts/master.blade.php','resources/views/layouts/navigation.blade.php'];
  foreach(glob('resources/views/modules/customers/*.blade.php') as $f) $files[]=$f;
  foreach(glob('resources/views/modules/inquiries/*.blade.php') as $f) $files[]=$f;
  foreach(glob('resources/views/modules/itineraries/*.blade.php') as $f) $files[]=$f;
  $used=[];
  foreach($files as $f){$c=file_get_contents($f); preg_match_all("/ui_phrase\\('([^']+)'/",$c,$m); foreach(($m[1]??[]) as $k)$used[$k]=true;}
  $miss=[];
  foreach(array_keys($used) as $k){$ev=$en[$k]??null; if($ev===null) continue; $lv=$locale[$k]??null; if($lv===null||trim((string)$lv)===''||(string)$lv===(string)$ev)$miss[]=$k;}
  return array_values(array_unique($miss));
};

$idxHs=[]; foreach($hs as $k=>$v){ if(trim((string)$v)!=='' && preg_match('/[^\x00-\x7F]/',(string)$v)) $idxHs[strtolower((string)$k)] = (string)$v; }
$idxHt=[]; foreach($ht as $k=>$v){ if(trim((string)$v)!=='' && preg_match('/[^\x00-\x7F]/',(string)$v)) $idxHt[strtolower((string)$k)] = (string)$v; }

$manualHs=[
'Actions'=>'操作','Activate'=>'启用','Activity'=>'活动','Activity Timeline'=>'活动时间线','Add History'=>'新增记录','Add Inquiry'=>'新增询问','Add Reminder'=>'新增提醒','Add Transport'=>'添加交通工具','Address'=>'地址','Airport'=>'机场','All Regions'=>'所有区域','Assigned'=>'已分配','Back'=>'返回','Cancel'=>'取消','Channel'=>'渠道','Close'=>'关闭','Communication History'=>'沟通记录','Contact At'=>'联系时间','Create Customer'=>'创建客户','Create Itinerary'=>'创建行程','Create a new inquiry record.'=>'创建新的询问记录。','Create a new itinerary record.'=>'创建新的行程记录。','Dashboard'=>'仪表板','Day :day'=>'第 :day 天','Day :day Start Point'=>'第 :day 天起点','Day :day End Point'=>'第 :day 天终点','Day :day Transport Units'=>'第 :day 天交通工具','Deactivate'=>'停用','Deadline'=>'截止日期','Description'=>'描述','Destination'=>'目的地','Detail'=>'详情','Display By Day'=>'按天显示','Done'=>'完成','Download PDF'=>'下载 PDF','Drag to reorder'=>'拖曳排序','Duplicate'=>'复制','Duration'=>'时长','Duration (Days)'=>'时长（天）','Duration (Nights)'=>'时长（晚）','Edit'=>'编辑','Edit Inquiry'=>'编辑询问','Edit Itinerary'=>'编辑行程','Email'=>'电子邮箱','Enabled'=>'已启用','End Point'=>'终点','Exclusions'=>'不包含项目','F&B'=>'餐饮','Filters'=>'筛选','Generate Quotation'=>'生成报价单','Highlight'=>'亮点','Hotel'=>'酒店','Hotels'=>'酒店','Import Now'=>'立即导入','Inclusions'=>'包含项目','Inclusions & Exclusions'=>'包含与不包含项目','Independent'=>'独立','Inquiries'=>'询问','Inquiry Overview'=>'询问概览','Inter Island Transfer'=>'岛际接送','Itineraries'=>'行程','Itinerary Detail'=>'行程详情','Itinerary Map'=>'行程地图','Loading...'=>'加载中...','Logout'=>'登出','Manage itinerary records.'=>'管理行程记录。','Mark Done'=>'标记完成','My Quotations'=>'我的报价单','Name'=>'名称','No Itinerary Yet'=>'暂无行程','Not set'=>'未设置','Notes'=>'备注','Order Number'=>'订单号','Preview PDF'=>'预览 PDF','Priority'=>'优先级','Profile'=>'个人资料','Reason'=>'原因','Remove item'=>'移除项目','Remove transport'=>'移除交通工具','Reset'=>'重置','Review complete inquiry information.'=>'查看完整询问信息。','Review complete itinerary information.'=>'查看完整行程信息。','Room'=>'房型','Save'=>'保存','Save Inquiry'=>'保存询问','Save Itinerary'=>'保存行程','Schedule by Day'=>'按天排程','Select F&B'=>'选择餐饮','Select activity'=>'选择活动','Select attraction'=>'选择景点','Select end point item'=>'选择终点项目','Select inter island transfer'=>'选择岛际接送','Select room'=>'选择房型','Select start point item'=>'选择起点项目','Select transport unit'=>'选择交通工具','Source'=>'来源','Start Point'=>'起点','Status'=>'状态','Summary'=>'摘要','Title'=>'标题','Transport Unit'=>'交通工具','Update Inquiry'=>'更新询问','Update Itinerary'=>'更新行程','Update customer information.'=>'更新客户信息。','Update inquiry information.'=>'更新询问信息。','Update itinerary information.'=>'更新行程信息。','View'=>'查看','View Detail'=>'查看详情','by'=>'由','yes'=>'是','no'=>'否','write here'=>'在此输入','breakfast'=>'早餐','lunch'=>'午餐','dinner'=>'晚餐','LOW'=>'低','NORMAL'=>'普通','HIGH'=>'高'
];
$manualHt=[
'Actions'=>'操作','Activate'=>'啟用','Activity'=>'活動','Activity Timeline'=>'活動時間軸','Add History'=>'新增紀錄','Add Inquiry'=>'新增詢問','Add Reminder'=>'新增提醒','Add Transport'=>'新增交通工具','Address'=>'地址','Airport'=>'機場','All Regions'=>'所有區域','Assigned'=>'已指派','Back'=>'返回','Cancel'=>'取消','Channel'=>'渠道','Close'=>'關閉','Communication History'=>'溝通紀錄','Contact At'=>'聯絡時間','Create Customer'=>'建立客戶','Create Itinerary'=>'建立行程','Create a new inquiry record.'=>'建立新的詢問紀錄。','Create a new itinerary record.'=>'建立新的行程紀錄。','Dashboard'=>'儀表板','Day :day'=>'第 :day 天','Day :day Start Point'=>'第 :day 天起點','Day :day End Point'=>'第 :day 天終點','Day :day Transport Units'=>'第 :day 天交通工具','Deactivate'=>'停用','Deadline'=>'截止日期','Description'=>'描述','Destination'=>'目的地','Detail'=>'詳情','Display By Day'=>'按天顯示','Done'=>'完成','Download PDF'=>'下載 PDF','Drag to reorder'=>'拖曳排序','Duplicate'=>'複製','Duration'=>'時長','Duration (Days)'=>'時長（天）','Duration (Nights)'=>'時長（晚）','Edit'=>'編輯','Edit Inquiry'=>'編輯詢問','Edit Itinerary'=>'編輯行程','Email'=>'電子郵件','Enabled'=>'已啟用','End Point'=>'終點','Exclusions'=>'不包含項目','F&B'=>'餐飲','Filters'=>'篩選','Generate Quotation'=>'產生報價單','Highlight'=>'亮點','Hotel'=>'飯店','Hotels'=>'飯店','Import Now'=>'立即匯入','Inclusions'=>'包含項目','Inclusions & Exclusions'=>'包含與不包含項目','Independent'=>'獨立','Inquiries'=>'詢問','Inquiry Overview'=>'詢問總覽','Inter Island Transfer'=>'島際接送','Itineraries'=>'行程','Itinerary Detail'=>'行程詳情','Itinerary Map'=>'行程地圖','Loading...'=>'載入中...','Logout'=>'登出','Manage itinerary records.'=>'管理行程紀錄。','Mark Done'=>'標記完成','My Quotations'=>'我的報價單','Name'=>'名稱','No Itinerary Yet'=>'尚無行程','Not set'=>'未設定','Notes'=>'備註','Order Number'=>'訂單號','Preview PDF'=>'預覽 PDF','Priority'=>'優先級','Profile'=>'個人資料','Reason'=>'原因','Remove item'=>'移除項目','Remove transport'=>'移除交通工具','Reset'=>'重設','Review complete inquiry information.'=>'檢視完整詢問資訊。','Review complete itinerary information.'=>'檢視完整行程資訊。','Room'=>'房型','Save'=>'儲存','Save Inquiry'=>'儲存詢問','Save Itinerary'=>'儲存行程','Schedule by Day'=>'按天排程','Select F&B'=>'選擇餐飲','Select activity'=>'選擇活動','Select attraction'=>'選擇景點','Select end point item'=>'選擇終點項目','Select inter island transfer'=>'選擇島際接送','Select room'=>'選擇房型','Select start point item'=>'選擇起點項目','Select transport unit'=>'選擇交通工具','Source'=>'來源','Start Point'=>'起點','Status'=>'狀態','Summary'=>'摘要','Title'=>'標題','Transport Unit'=>'交通工具','Update Inquiry'=>'更新詢問','Update Itinerary'=>'更新行程','Update customer information.'=>'更新客戶資訊。','Update inquiry information.'=>'更新詢問資訊。','Update itinerary information.'=>'更新行程資訊。','View'=>'查看','View Detail'=>'查看詳情','by'=>'由','yes'=>'是','no'=>'否','write here'=>'在此輸入','breakfast'=>'早餐','lunch'=>'午餐','dinner'=>'晚餐','LOW'=>'低','NORMAL'=>'普通','HIGH'=>'高'
];

$fill = function(array &$loc,array $miss,array $idx,array $manual,array $en){
  $c=0;
  foreach($miss as $k){
    if(isset($manual[$k])){$loc[$k]=$manual[$k];$c++;continue;}
    $lk=strtolower($k);
    if(isset($idx[$lk])){$loc[$k]=$idx[$lk];$c++;continue;}
    $ev=(string)($en[$k]??'');
    $lev=strtolower($ev);
    if(isset($idx[$lev])){$loc[$k]=$idx[$lev];$c++;continue;}
  }
  return $c;
};
$missHs=$collectMissing($hs);
$missHt=$collectMissing($ht);
$c1=$fill($hs,$missHs,$idxHs,$manualHs,$en);
$c2=$fill($ht,$missHt,$idxHt,$manualHt,$en);
ksort($hs,SORT_NATURAL|SORT_FLAG_CASE); ksort($ht,SORT_NATURAL|SORT_FLAG_CASE);
file_put_contents('lang/zh_Hans/ui_core.php',"<?php\n\nreturn ".var_export($hs,true).";\n");
file_put_contents('lang/zh_Hant/ui_core.php',"<?php\n\nreturn ".var_export($ht,true).";\n");
echo "filled_hans=$c1\nfilled_hant=$c2\n";
