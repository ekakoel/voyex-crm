<?php
$paths=['en'=>'lang/en/ui_core.php','zh_Hant'=>'lang/zh_Hant/ui_core.php','zh_Hans'=>'lang/zh_Hans/ui_core.php'];
$data=[];
foreach($paths as $lc=>$p){$data[$lc]=include $p;}

function cleanKey(string $k): string {
  $k = preg_replace('/_+/', '_', $k) ?? $k;
  return trim($k, '_');
}

// normalize keys per locale
foreach($data as $lc=>$arr){
  $norm=[];
  foreach($arr as $k=>$v){
    $nk=cleanKey((string)$k);
    if(!array_key_exists($nk,$norm)) $norm[$nk]=$v;
  }
  $data[$lc]=$norm;
}

// align zh keyset to en keyset only
$enKeys=array_keys($data['en']);
$enSet=array_flip($enKeys);
foreach(['zh_Hant','zh_Hans'] as $lc){
  $arr=[];
  foreach($enKeys as $k){
    $arr[$k] = array_key_exists($k,$data[$lc]) ? $data[$lc][$k] : ($data['en'][$k] ?? $k);
  }
  $data[$lc]=$arr;
}

function ex(array $a){$o="<?php\n\nreturn [\n";foreach($a as $k=>$v){$k=str_replace(["\\","'"],["\\\\","\\'"],$k);if(is_string($v)){$v=str_replace(["\\","'"],["\\\\","\\'"],$v);$o.="    '{$k}' => '{$v}',\n";}else{$o.="    '{$k}' => ".var_export($v,true).",\n";}}return $o."];\n";}
foreach($paths as $lc=>$p){file_put_contents($p,ex($data[$lc]));echo "$lc keys=".count($data[$lc])."\n";}
