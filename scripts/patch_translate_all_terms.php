<?php
$targets = [
  'zh_Hant' => 'lang/zh_Hant/ui_core.php',
  'zh_Hans' => 'lang/zh_Hans/ui_core.php',
];
$en = include 'lang/en/ui_core.php';

$dict = [
  'zh_Hant' => [
    'Create'=>'建立','Update'=>'更新','Edit'=>'編輯','Delete'=>'刪除','Save'=>'儲存','Add'=>'新增','Back'=>'返回','View'=>'查看','Detail'=>'詳情','Details'=>'詳情',
    'Manage'=>'管理','Data'=>'資料','Information'=>'資訊','Info'=>'資訊','Status'=>'狀態','Active'=>'啟用','Inactive'=>'停用','Actions'=>'操作','Action'=>'操作',
    'Dashboard'=>'儀表板','Overview'=>'概覽','Title'=>'標題','Name'=>'名稱','Code'=>'代碼','Type'=>'類型','Category'=>'分類','Description'=>'說明','Notes'=>'備註',
    'Destination'=>'目的地','Inquiry'=>'詢價','Quotation'=>'報價單','Booking'=>'預訂','Invoice'=>'發票','Customer'=>'客戶','Vendor'=>'供應商','Provider'=>'供應商',
    'Airport'=>'機場','Hotel'=>'酒店','Hotels'=>'酒店','Room'=>'房型','Rooms'=>'房型','Itinerary'=>'行程','Activity'=>'活動','Activities'=>'活動','Transport'=>'交通','Transports'=>'交通',
    'Food'=>'餐飲','Beverage'=>'餐飲','FoodBeverage'=>'餐飲','User'=>'使用者','Users'=>'使用者','Role'=>'角色','Roles'=>'角色','Permission'=>'權限','Permissions'=>'權限',
    'Generate'=>'產生','Preview'=>'預覽','Download'=>'下載','PDF'=>'PDF','Map'=>'地圖','Schedule'=>'行程','Day'=>'天','Display'=>'顯示','Duration'=>'天數','Total'=>'總計',
    'Priority'=>'優先級','High'=>'高','Normal'=>'一般','Low'=>'低','Approved'=>'已核准','Final'=>'最終','Processed'=>'已處理','Draft'=>'草稿','Rejected'=>'已拒絕','Pending'=>'待處理',
    'Filter'=>'篩選','Filters'=>'篩選','Search'=>'搜尋','Reset'=>'重設','Quick'=>'快速','Guide'=>'指南','List'=>'清單','Page'=>'頁','per page'=>'每頁',
    'Created'=>'已建立','Updated'=>'已更新','Created At'=>'建立時間','Updated At'=>'更新時間','Created By'=>'建立者','Updated By'=>'更新者',
    'Follow-up'=>'跟進','Reminder'=>'提醒','Approval'=>'審批','Validation'=>'驗證','Validated'=>'已驗證','Unknown'=>'未知',
  ],
  'zh_Hans' => [
    'Create'=>'创建','Update'=>'更新','Edit'=>'编辑','Delete'=>'删除','Save'=>'保存','Add'=>'新增','Back'=>'返回','View'=>'查看','Detail'=>'详情','Details'=>'详情',
    'Manage'=>'管理','Data'=>'资料','Information'=>'信息','Info'=>'信息','Status'=>'状态','Active'=>'启用','Inactive'=>'停用','Actions'=>'操作','Action'=>'操作',
    'Dashboard'=>'仪表板','Overview'=>'概览','Title'=>'标题','Name'=>'名称','Code'=>'代码','Type'=>'类型','Category'=>'分类','Description'=>'说明','Notes'=>'备注',
    'Destination'=>'目的地','Inquiry'=>'询价','Quotation'=>'报价单','Booking'=>'预订','Invoice'=>'发票','Customer'=>'客户','Vendor'=>'供应商','Provider'=>'供应商',
    'Airport'=>'机场','Hotel'=>'酒店','Hotels'=>'酒店','Room'=>'房型','Rooms'=>'房型','Itinerary'=>'行程','Activity'=>'活动','Activities'=>'活动','Transport'=>'交通','Transports'=>'交通',
    'Food'=>'餐饮','Beverage'=>'餐饮','FoodBeverage'=>'餐饮','User'=>'用户','Users'=>'用户','Role'=>'角色','Roles'=>'角色','Permission'=>'权限','Permissions'=>'权限',
    'Generate'=>'生成','Preview'=>'预览','Download'=>'下载','PDF'=>'PDF','Map'=>'地图','Schedule'=>'行程','Day'=>'天','Display'=>'显示','Duration'=>'天数','Total'=>'总计',
    'Priority'=>'优先级','High'=>'高','Normal'=>'普通','Low'=>'低','Approved'=>'已批准','Final'=>'最终','Processed'=>'已处理','Draft'=>'草稿','Rejected'=>'已拒绝','Pending'=>'待处理',
    'Filter'=>'筛选','Filters'=>'筛选','Search'=>'搜索','Reset'=>'重置','Quick'=>'快速','Guide'=>'指南','List'=>'列表','Page'=>'页','per page'=>'每页',
    'Created'=>'已创建','Updated'=>'已更新','Created At'=>'创建时间','Updated At'=>'更新时间','Created By'=>'创建者','Updated By'=>'更新者',
    'Follow-up'=>'跟进','Reminder'=>'提醒','Approval'=>'审批','Validation'=>'验证','Validated'=>'已验证','Unknown'=>'未知',
  ],
];

function exportArray(array $arr): string {
  $out = "<?php\n\nreturn [\n";
  foreach ($arr as $k => $v) {
    $ks = str_replace(["\\", "'"], ["\\\\", "\\'"], (string)$k);
    if (is_string($v)) {
      $vs = str_replace(["\\", "'"], ["\\\\", "\\'"], $v);
      $out .= "    '{$ks}' => '{$vs}',\n";
    } else {
      $out .= "    '{$ks}' => " . var_export($v, true) . ",\n";
    }
  }
  $out .= "];\n";
  return $out;
}

foreach ($targets as $locale => $file) {
  $arr = include $file;

  // phrase map from already translated sibling keys
  $phraseMap = [];
  foreach ($arr as $k => $v) {
    if (!isset($en[$k]) || !is_string($en[$k]) || !is_string($v)) continue;
    if ($v === '' || $v === $en[$k]) continue;
    if (preg_match('/[A-Za-z]/', $v)) continue;
    $phraseMap[$en[$k]] = $v;
  }

  $changed = 0;
  foreach ($arr as $k => $v) {
    if (!is_string($v) || $v === '' || !preg_match('/[A-Za-z]/', $v)) continue;

    $nv = $v;

    if (isset($phraseMap[$v])) {
      $nv = $phraseMap[$v];
    } else {
      // high-confidence full phrase map first
      foreach ($dict[$locale] as $enWord => $zhWord) {
        $pattern = '/\b' . preg_quote($enWord, '/') . '\b/u';
        $nv = preg_replace($pattern, $zhWord, $nv);
      }

      // common postfix/prefix templates
      $nv = str_replace('Create a new ', $locale==='zh_Hant' ? '建立新的' : '创建新的', $nv);
      $nv = str_replace('Update ', $locale==='zh_Hant' ? '更新' : '更新', $nv);
      $nv = str_replace('Manage ', $locale==='zh_Hant' ? '管理' : '管理', $nv);
      $nv = str_replace(' data.', $locale==='zh_Hant' ? '資料。' : '资料。', $nv);
      $nv = str_replace(' details.', $locale==='zh_Hant' ? '詳情。' : '详情。', $nv);
      $nv = str_replace(' record.', $locale==='zh_Hant' ? '資料。' : '资料。', $nv);
      $nv = str_replace('/page', $locale==='zh_Hant' ? '/頁' : '/页', $nv);
    }

    if ($nv !== $v) {
      $arr[$k] = $nv;
      $changed++;
    }
  }

  file_put_contents($file, exportArray($arr));
  echo "$locale changed=$changed\n";
}
