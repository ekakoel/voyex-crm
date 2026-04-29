<?php
$files=['lang/en/ui_core.php','lang/zh_Hant/ui_core.php','lang/zh_Hans/ui_core.php'];

function exportArray(array $arr): string {
  $out="<?php\n\nreturn [\n";
  foreach($arr as $k=>$v){
    $ks=str_replace(["\\","'"],["\\\\","\\'"],(string)$k);
    if(is_string($v)){$vs=str_replace(["\\","'"],["\\\\","\\'"],$v);$out.="    '{$ks}' => '{$vs}',\n";}
    else{$out.="    '{$ks}' => ".var_export($v,true).",\n";}
  }
  return $out."];\n";
}

foreach($files as $f){
  $arr=include $f;
  $add=0;$del=0;
  foreach(array_keys($arr) as $k){
    if(str_starts_with($k,'modules_company_settings_')){
      $base=substr($k,strlen('modules_company_settings_'));
      if($base!=='' && !array_key_exists($base,$arr)){
        $arr[$base]=$arr[$k];
        $add++;
      }
    }
  }
  foreach(array_keys($arr) as $k){
    if(str_starts_with($k,'modules_company_settings_')){ unset($arr[$k]); $del++; }
  }
  file_put_contents($f,exportArray($arr));
  echo "$f add=$add del=$del\n";
}
