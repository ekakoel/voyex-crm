<?php
$targets = [
 'zh_Hant' => 'lang/zh_Hant/ui_core.php',
 'zh_Hans' => 'lang/zh_Hans/ui_core.php',
];

$nouns = [
 'Activity'=>['活動','活动'],'Airport'=>['機場','机场'],'Attraction'=>['景點','景点'],'Booking'=>['預訂','预订'],'Customer'=>['客戶','客户'],'Destination'=>['目的地','目的地'],'Employee'=>['員工','员工'],'Hotel'=>['酒店','酒店'],'Inquiry'=>['詢價','询价'],'Invoice'=>['發票','发票'],'Itinerary'=>['行程','行程'],'Quotation'=>['報價單','报价单'],'Role'=>['角色','角色'],'Transport'=>['交通','交通'],'Vendor'=>['供應商','供应商'],'User'=>['使用者','用户'],'Currency'=>['幣別','币别'],'Provider'=>['供應商','供应商']
];

function trNoun(string $text, string $locale, array $nouns): string {
  foreach($nouns as $en=>$pair){
    $zh = $locale==='zh_Hant' ? $pair[0] : $pair[1];
    $text = preg_replace('/\b'.preg_quote($en,'/').'s\b/u', $zh, $text);
    $text = preg_replace('/\b'.preg_quote($en,'/').'\b/u', $zh, $text);
  }
  return $text;
}

function convert(string $v, string $locale, array $nouns): string {
  $orig = $v;

  $v = str_replace('Create a new ', $locale==='zh_Hant' ? '建立新的' : '创建新的', $v);
  $v = str_replace(' record.', $locale==='zh_Hant' ? '資料。' : '资料。', $v);
  $v = str_replace('Update ', $locale==='zh_Hant' ? '更新' : '更新', $v);
  $v = str_replace(' details.', $locale==='zh_Hant' ? '詳情。' : '详情。', $v);
  $v = str_replace('Manage ', $locale==='zh_Hant' ? '管理' : '管理', $v);
  $v = str_replace(' data.', $locale==='zh_Hant' ? '資料。' : '资料。', $v);
  $v = str_replace('Review ', $locale==='zh_Hant' ? '查看' : '查看', $v);
  $v = str_replace(' detail information.', $locale==='zh_Hant' ? '詳情資訊。' : '详情信息。', $v);
  $v = str_replace('Save ', $locale==='zh_Hant' ? '儲存' : '保存', $v);
  $v = str_replace('Add ', $locale==='zh_Hant' ? '新增' : '新增', $v);
  $v = str_replace('Edit ', $locale==='zh_Hant' ? '編輯' : '编辑', $v);
  $v = str_replace('Back to ', $locale==='zh_Hant' ? '返回' : '返回', $v);

  $v = trNoun($v, $locale, $nouns);

  // generic suffixes
  $v = str_replace(' Detail', $locale==='zh_Hant' ? '詳情' : '详情', $v);
  $v = str_replace(' Information', $locale==='zh_Hant' ? '資訊' : '信息', $v);

  return $v === $orig ? $orig : $v;
}

function exportArray(array $arr): string {
  $out="<?php\n\nreturn [\n";
  foreach($arr as $k=>$v){
    $ks=str_replace(["\\","'"],["\\\\","\\'"],(string)$k);
    if(is_string($v)){
      $vs=str_replace(["\\","'"],["\\\\","\\'"],$v);
      $out.="    '{$ks}' => '{$vs}',\n";
    } else {
      $out.="    '{$ks}' => ".var_export($v,true).",\n";
    }
  }
  $out.= "];\n";
  return $out;
}

foreach($targets as $locale=>$file){
  $arr=include $file;
  $count=0;
  foreach($arr as $k=>$v){
    if(!is_string($v) || $v==='') continue;
    if(!preg_match('/[A-Za-z]/',$v)) continue;
    $nv = convert($v,$locale,$nouns);
    if($nv !== $v && !preg_match('/^[\x00-\x7F]+$/',$nv)){
      $arr[$k]=$nv; $count++;
    }
  }
  file_put_contents($file, exportArray($arr));
  echo "$locale template_patched=$count\n";
}
