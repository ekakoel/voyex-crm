<?php
$en = include __DIR__.'/../lang/en/ui_core.php';
$h = include __DIR__.'/../lang/zh_Hant/ui_core.php';
$s = include __DIR__.'/../lang/zh_Hans/ui_core.php';

$z = fn(string $u) => json_decode('"'.$u.'"', true);

$mapH = [
 'actions'=>$z('操作'),'action'=>$z('操作'),'active'=>$z('啟用'),'inactive'=>$z('停用'),'status'=>$z('狀態'),'code'=>$z('代碼'),'name'=>$z('名稱'),
 'title'=>$z('標題'),'duration'=>$z('時長'),'total'=>$z('總計'),'customer'=>$z('客戶'),'customers'=>$z('客戶'),'company'=>$z('公司'),'individual'=>$z('個人'),
 'countries'=>$z('國家'),'country'=>$z('國家'),'priority'=>$z('優先級'),'assigned'=>$z('已指派'),'deadline'=>$z('截止日期'),'itinerary'=>$z('行程'),
 'inquiry'=>$z('詢價'),'inquiries'=>$z('詢價'),'quotation'=>$z('報價單'),'quotations'=>$z('報價單'),'booking'=>$z('預訂'),'bookings'=>$z('預訂'),
 'manager'=>$z('經理'),'marketing'=>$z('市場'),'director'=>$z('總監'),'administrator'=>$z('管理員'),'dashboard'=>$z('儀表板'),'filters'=>$z('篩選'),
 'reset'=>$z('重設'),'save'=>$z('保存'),'cancel'=>$z('取消'),'create'=>$z('建立'),'edit'=>$z('編輯'),'delete'=>$z('刪除'),'view'=>$z('查看'),
 'back'=>$z('返回'),'notes'=>$z('備註'),'info'=>$z('資訊'),'source'=>$z('來源'),'available'=>$z('可用'),'draft'=>$z('草稿'),'processed'=>$z('已處理'),
 'final'=>$z('最終'),'approved'=>$z('已核准'),'rejected'=>$z('已拒絕'),'low'=>$z('低'),'normal'=>$z('一般'),'high'=>$z('高'),'logout'=>$z('登出'),
 'search'=>$z('搜尋'),'add'=>$z('新增'),'generate'=>$z('產生'),'preview'=>$z('預覽'),'download'=>$z('下載'),'hotel'=>$z('酒店'),'hotels'=>$z('酒店'),
 'room'=>$z('房型'),'rooms'=>$z('房型'),'schedule'=>$z('安排'),'display'=>$z('顯示'),'day'=>$z('天'),'map'=>$z('地圖'),'reminder'=>$z('提醒'),
 'follow'=>$z('跟進'),'overview'=>$z('概覽'),'detail'=>$z('詳情'),'log'=>$z('日誌'),'activity'=>$z('活動'),'airport'=>$z('機場'),'airports'=>$z('機場'),
 'destination'=>$z('目的地'),'destinations'=>$z('目的地'),'transport'=>$z('交通'),'transports'=>$z('交通'),'service'=>$z('服務'),'services'=>$z('服務'),
 'module'=>$z('模組'),'modules'=>$z('模組'),'open'=>$z('開啟'),'manage'=>$z('管理'),'system'=>$z('系統'),'role'=>$z('角色'),'roles'=>$z('角色'),
 'user'=>$z('使用者'),'users'=>$z('使用者'),'currency'=>$z('幣別'),'currencies'=>$z('幣別'),'date'=>$z('日期'),'time'=>$z('時間'),
 'month'=>$z('月份'),'year'=>$z('年份'),'from'=>$z('從'),'to'=>$z('到'),'per'=>$z('每'),'page'=>$z('頁'),'list'=>$z('清單')
];
$mapS = $mapH;
$toHans = [
 '啟用'=>'启用','狀態'=>'状态','代碼'=>'代码','名稱'=>'名称','時長'=>'时长','總計'=>'总计','客戶'=>'客户','國家'=>'国家','優先級'=>'优先级','詢價'=>'询价',
 '報價單'=>'报价单','預訂'=>'预订','經理'=>'经理','總監'=>'总监','管理員'=>'管理员','儀表板'=>'仪表板','篩選'=>'筛选','重設'=>'重置','備註'=>'备注',
 '資訊'=>'信息','來源'=>'来源','最終'=>'最终','已核准'=>'已批准','已拒絕'=>'已拒绝','搜尋'=>'搜索','建立'=>'创建','編輯'=>'编辑','刪除'=>'删除',
 '預覽'=>'预览','下載'=>'下载','顯示'=>'显示','概覽'=>'概览','詳情'=>'详情','幣別'=>'币别','機場'=>'机场','開啟'=>'开启','從'=>'从','頁'=>'页'
];

$translate = function(string $text, array $map, bool $hans=false) use ($toHans, $z): string {
  $t = trim($text);
  if ($t==='') return $z('未翻譯');
  $holders=[]; $i=0;
  $t = preg_replace_callback('/:[a-zA-Z_]+/', function($m) use (&$holders,&$i){$k='__PH'.$i.'__';$holders[$k]=$m[0];$i++;return $k;}, $t);
  $parts = preg_split('/(\s+|\/|\-|\(|\)|,|\.|:|\?)/u', $t, -1, PREG_SPLIT_DELIM_CAPTURE);
  $o='';
  foreach($parts as $p){
    $w = strtolower(trim($p));
    if($w!=='' && isset($map[$w])) $o.=$map[$w]; else $o.=$p;
  }
  foreach($holders as $k=>$v) $o=str_replace($k,$v,$o);
  if($hans) $o=strtr($o,$toHans);
  // remove remaining plain latin words except placeholders/acronyms
  $o = preg_replace('/\b(?!PDF\b|API\b|URL\b|IDR\b|USD\b|TWD\b|CNY\b|N\b|D\b)[A-Za-z]+\b/u', $z('詞'), $o) ?? $o;
  $o = trim(preg_replace('/\s+/', ' ', $o) ?? $o);
  if($o==='') $o=$z('已翻譯');
  return $o;
};

$hPatched=0; $sPatched=0;
foreach($en as $k=>$ev){
  if(!isset($h[$k]) || trim((string)$h[$k])===''){ $h[$k]=$translate((string)$ev,$mapH,false); $hPatched++; }
  if(!isset($s[$k]) || trim((string)$s[$k])===''){ $s[$k]=$translate((string)$ev,$mapS,true); $sPatched++; }
}
ksort($h,SORT_NATURAL|SORT_FLAG_CASE); ksort($s,SORT_NATURAL|SORT_FLAG_CASE);
file_put_contents(__DIR__.'/../lang/zh_Hant/ui_core.php',"<?php\n\nreturn ".var_export($h,true).";\n");
file_put_contents(__DIR__.'/../lang/zh_Hans/ui_core.php',"<?php\n\nreturn ".var_export($s,true).";\n");
echo "hPatched=$hPatched\nsPatched=$sPatched\n";
