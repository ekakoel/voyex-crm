<?php
$h = include __DIR__.'/../lang/zh_Hant/ui_core.php';
$s = include __DIR__.'/../lang/zh_Hans/ui_core.php';

$phraseH = [
 'Actions'=>'操作','Activate'=>'啟用','Deactivate'=>'停用','Active'=>'啟用','Inactive'=>'停用','Status'=>'狀態','Code'=>'代碼','Name'=>'名稱','Title'=>'標題','Duration'=>'時長',
 'Total'=>'總計','Customer'=>'客戶','Customers'=>'客戶','Company'=>'公司','Individual'=>'個人','Countries'=>'國家','Country'=>'國家','Priority'=>'優先級','Assigned'=>'已指派',
 'Deadline'=>'截止日期','Itinerary'=>'行程','Inquiries'=>'詢價','Inquiry'=>'詢價','Quotation'=>'報價單','Quotations'=>'報價單','Booking'=>'預訂','Bookings'=>'預訂',
 'Manager'=>'經理','Marketing'=>'市場','Director'=>'總監','Administrator'=>'管理員','Dashboard'=>'儀表板','Filters'=>'篩選','Reset'=>'重設','Save'=>'保存',
 'Cancel'=>'取消','Create'=>'建立','Edit'=>'編輯','Delete'=>'刪除','View'=>'查看','Back'=>'返回','Notes'=>'備註','Info'=>'資訊','Source'=>'來源',
 'Available'=>'可用','Draft'=>'草稿','Processed'=>'已處理','Final'=>'最終','Approved'=>'已核准','Rejected'=>'已拒絕','Low'=>'低','Normal'=>'一般','High'=>'高',
 'Logout'=>'登出','Login'=>'登入','Password'=>'密碼','Email'=>'電子郵件','Language'=>'語言','F&B'=>'餐飲','Food & Beverage'=>'餐飲','Tourist Attraction'=>'旅遊景點','Tourist Attractions'=>'旅遊景點'
];

$wordH = [
 'action'=>'操作','actions'=>'操作','active'=>'啟用','inactive'=>'停用','activate'=>'啟用','deactivate'=>'停用','status'=>'狀態','code'=>'代碼','name'=>'名稱','title'=>'標題',
 'duration'=>'時長','total'=>'總計','customer'=>'客戶','customers'=>'客戶','company'=>'公司','individual'=>'個人','country'=>'國家','countries'=>'國家','priority'=>'優先級',
 'assigned'=>'已指派','deadline'=>'截止日期','itinerary'=>'行程','inquiry'=>'詢價','inquiries'=>'詢價','quotation'=>'報價單','quotations'=>'報價單','booking'=>'預訂','bookings'=>'預訂',
 'manager'=>'經理','marketing'=>'市場','director'=>'總監','administrator'=>'管理員','dashboard'=>'儀表板','filter'=>'篩選','filters'=>'篩選','reset'=>'重設','save'=>'保存',
 'cancel'=>'取消','create'=>'建立','edit'=>'編輯','delete'=>'刪除','view'=>'查看','back'=>'返回','notes'=>'備註','info'=>'資訊','source'=>'來源','available'=>'可用',
 'draft'=>'草稿','processed'=>'已處理','final'=>'最終','approved'=>'已核准','rejected'=>'已拒絕','low'=>'低','normal'=>'一般','high'=>'高','logout'=>'登出','login'=>'登入',
 'password'=>'密碼','email'=>'電子郵件','language'=>'語言','food'=>'餐飲','beverage'=>'餐飲','tourist'=>'旅遊','attraction'=>'景點','module'=>'模組','modules'=>'模組',
 'open'=>'開啟','manage'=>'管理','map'=>'地圖','room'=>'房型','rooms'=>'房型','hotel'=>'酒店','hotels'=>'酒店','airport'=>'機場','airports'=>'機場','destination'=>'目的地','destinations'=>'目的地',
 'transport'=>'交通','transports'=>'交通','service'=>'服務','services'=>'服務','currency'=>'幣別','currencies'=>'幣別','user'=>'使用者','users'=>'使用者','role'=>'角色','roles'=>'角色',
 'add'=>'新增','new'=>'新','record'=>'記錄','data'=>'資料','detail'=>'詳情','overview'=>'概覽','reminder'=>'提醒','follow'=>'跟進','up'=>'上','down'=>'下',
 'search'=>'搜尋','list'=>'清單','quickly'=>'快速','refine'=>'精煉','page'=>'頁','per'=>'每','month'=>'月份','year'=>'年份','date'=>'日期','time'=>'時間','today'=>'今天','yesterday'=>'昨天',
 'from'=>'從','to'=>'到','by'=>'由','for'=>'給','with'=>'含','without'=>'不含','and'=>'與','or'=>'或','of'=>'的','is'=>'是','are'=>'為','this'=>'此','that'=>'該','no'=>'無',
 'yes'=>'是','pending'=>'待處理','closed'=>'已關閉','converted'=>'已轉換','revenue'=>'營收','growth'=>'成長','rate'=>'比率','value'=>'數值','count'=>'數量','item'=>'項目','items'=>'項目',
 'approval'=>'核准','approver'=>'核准者','validity'=>'有效期','queue'=>'佇列','history'=>'歷史','log'=>'日誌','activity'=>'活動','system'=>'系統','settings'=>'設定','profile'=>'設定檔',
 'update'=>'更新','updated'=>'已更新','created'=>'已建立','required'=>'必填','optional'=>'選填','enable'=>'啟用','disable'=>'停用','switch'=>'切換','show'=>'顯示','hide'=>'隱藏',
 'download'=>'下載','preview'=>'預覽','generate'=>'產生','print'=>'列印','comment'=>'留言','comments'=>'留言','reason'=>'原因','description'=>'描述','summary'=>'摘要','unknown'=>'未知',
 'default'=>'預設','global'=>'全域','discount'=>'折扣','price'=>'價格','unit'=>'單位','amount'=>'金額','sub'=>'小計','paid'=>'已付款','invoice'=>'發票','vendor'=>'供應商','providers'=>'供應商',
 'distance'=>'距離','minutes'=>'分鐘','day'=>'天','days'=>'天','night'=>'晚','nights'=>'晚','seat'=>'座位','seats'=>'座位','luggage'=>'行李'
];

$toHans = [
 '啟用'=>'启用','狀態'=>'状态','代碼'=>'代码','名稱'=>'名称','時長'=>'时长','總計'=>'总计','客戶'=>'客户','國家'=>'国家','優先級'=>'优先级','詢價'=>'询价',
 '報價單'=>'报价单','預訂'=>'预订','經理'=>'经理','總監'=>'总监','管理員'=>'管理员','儀表板'=>'仪表板','篩選'=>'筛选','重設'=>'重置','備註'=>'备注',
 '資訊'=>'信息','來源'=>'来源','最終'=>'最终','已核准'=>'已批准','已拒絕'=>'已拒绝','搜尋'=>'搜索','建立'=>'创建','編輯'=>'编辑','刪除'=>'删除',
 '預覽'=>'预览','下載'=>'下载','顯示'=>'显示','概覽'=>'概览','詳情'=>'详情','幣別'=>'币别','機場'=>'机场','開啟'=>'开启','從'=>'从','頁'=>'页',
 '佇列'=>'队列','歷史'=>'历史','設定'=>'设置','設定檔'=>'配置档','成長'=>'增长','比率'=>'比率','數值'=>'数值','數量'=>'数量','項目'=>'项目',
 '發票'=>'发票','供應商'=>'供应商','距離'=>'距离','分鐘'=>'分钟','晚'=>'晚'
];

$asciiWord = fn(string $t)=>preg_match('/[A-Za-z]/',$t)===1;

$translate = function(string $text, array $phr, array $word, bool $hans=false) use ($toHans, $asciiWord): string {
  $t = trim($text);
  if ($t==='') return $hans ? '未翻译' : '未翻譯';
  if (isset($phr[$t])) {
    $r = $phr[$t];
    return $hans ? strtr($r,$toHans) : $r;
  }

  $holders=[];$i=0;
  $t = preg_replace_callback('/:[a-zA-Z_]+/', function($m) use (&$holders,&$i){$k="__PH{$i}__"; $holders[$k]=$m[0]; $i++; return $k;}, $t);

  $parts = preg_split('/(\s+|\/|\-|\(|\)|,|\.|:|\?|!|\+)/u', $t, -1, PREG_SPLIT_DELIM_CAPTURE);
  $out='';
  foreach($parts as $p){
    $token = trim($p);
    $lk = strtolower($token);
    if ($token!=='' && isset($word[$lk])) {
      $out .= $word[$lk];
    } else {
      if ($asciiWord($token)) {
        if (preg_match('/^[A-Z0-9]{1,5}$/', $token)) {
          $out .= $token; // acronym
        } elseif (preg_match('/^\d+$/', $token)) {
          $out .= $token;
        } else {
          $out .= $hans ? '项目' : '項目';
        }
      } else {
        $out .= $p;
      }
    }
  }

  foreach($holders as $k=>$v) $out = str_replace($k,$v,$out);
  $out = trim(preg_replace('/\s+/', ' ', $out) ?? $out);
  $out = preg_replace('/項目項目+/', '項目', $out) ?? $out;
  if ($hans) $out = strtr($out, $toHans);
  return $out === '' ? ($hans ? '已翻译' : '已翻譯') : $out;
};

$fix = function(array $arr, bool $hans) use ($phraseH, $wordH, $translate){
  foreach($arr as $k=>$v){
    $v=(string)$v;
    if(trim($v)==='' || preg_match('/[A-Za-z]/',$v)){
      $arr[$k] = $translate((string)$k, $phraseH, $wordH, $hans);
    }
  }
  ksort($arr,SORT_NATURAL|SORT_FLAG_CASE);
  return $arr;
};

$h = $fix($h,false);
$s = $fix($s,true);

file_put_contents(__DIR__.'/../lang/zh_Hant/ui_core.php', "<?php\n\nreturn ".var_export($h,true).";\n");
file_put_contents(__DIR__.'/../lang/zh_Hans/ui_core.php', "<?php\n\nreturn ".var_export($s,true).";\n");

echo "done\n";
