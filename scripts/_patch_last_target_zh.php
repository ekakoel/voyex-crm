<?php
$hs=include 'lang/zh_Hans/ui_core.php';
$ht=include 'lang/zh_Hant/ui_core.php';
$mapS=['Install Aplikasi'=>'安装应用','Install Sekarang'=>'立即安装','Itinerary Exclude'=>'行程不包含','Itinerary Include'=>'行程包含','Light mode on'=>'浅色模式已开启','Main Experience'=>'主要体验','Nanti'=>'稍后','VOYEX CRM'=>'VOYEX CRM','You'=>'你','day short'=>'第:day天','star'=>'星级','system'=>'系统','thumbnail alt'=>':name 缩略图','tracking changes'=>'追踪此询问的所有变更。','type company desc'=>'用于企业或公司客户。','type individual desc'=>'用于个人客户。'];
$mapT=['Install Aplikasi'=>'安裝應用程式','Install Sekarang'=>'立即安裝','Itinerary Exclude'=>'行程不包含','Itinerary Include'=>'行程包含','Light mode on'=>'淺色模式已開啟','Main Experience'=>'主要體驗','Nanti'=>'稍後','VOYEX CRM'=>'VOYEX CRM','You'=>'你','day short'=>'第:day天','star'=>'星級','system'=>'系統','thumbnail alt'=>':name 縮圖','tracking changes'=>'追蹤此詢問的所有變更。','type company desc'=>'用於企業或公司客戶。','type individual desc'=>'用於個人客戶。'];
foreach($mapS as $k=>$v){$hs[$k]=$v;} foreach($mapT as $k=>$v){$ht[$k]=$v;}
ksort($hs,SORT_NATURAL|SORT_FLAG_CASE); ksort($ht,SORT_NATURAL|SORT_FLAG_CASE);
file_put_contents('lang/zh_Hans/ui_core.php',"<?php\n\nreturn ".var_export($hs,true).";\n");
file_put_contents('lang/zh_Hant/ui_core.php',"<?php\n\nreturn ".var_export($ht,true).";\n");
