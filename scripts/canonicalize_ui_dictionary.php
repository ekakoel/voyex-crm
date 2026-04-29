<?php
require_once 'vendor/autoload.php';
use Illuminate\Support\Str;

$files=[
  'en'=>'lang/en/ui_core.php',
  'zh_Hant'=>'lang/zh_Hant/ui_core.php',
  'zh_Hans'=>'lang/zh_Hans/ui_core.php',
];

$src=[];
foreach($files as $lc=>$f){ $src[$lc]=include $f; }
$en=$src['en'];

function coreKey(string $value): string {
  $key = trim($value);
  $key = str_replace(['-', ' '], '_', $key);
  $key = trim($key, '.');
  if (str_starts_with($key, 'ui.')) $key = substr($key, 3);
  return Str::snake(str_replace('.', '_', $key));
}

$legacyMap=[]; // old normalized key => canonical key
$canonicalOrder=[];
$canonicalByOld=[];

foreach($en as $oldKey=>$enVal){
  $oldNorm = coreKey((string)$oldKey);
  $candidate = is_string($enVal)&&$enVal!=='' ? coreKey($enVal) : $oldNorm;
  if($candidate==='') $candidate=$oldNorm;
  // avoid overly generic duplicates mapping conflict by preserving first
  if(!isset($canonicalOrder[$candidate])) $canonicalOrder[$candidate]=[];
  $canonicalOrder[$candidate][]=$oldKey;
  $legacyMap[$oldNorm]=$candidate;
  $canonicalByOld[$oldKey]=$candidate;
}

// Build canonical locale arrays
$out=[];
foreach($files as $lc=>$path){
  $arr=$src[$lc];
  $canon=[];
  foreach($arr as $oldKey=>$val){
    $ck=$canonicalByOld[$oldKey] ?? coreKey((string)$oldKey);
    // keep first non-empty translation; if existing equals key-token replace with better localized val
    if(!array_key_exists($ck,$canon)){
      $canon[$ck]=$val;
      continue;
    }
    $existing=$canon[$ck];
    $existingScore = is_string($existing) ? (preg_match('/[A-Za-z]/',$existing)?0:1) : 0;
    $newScore = is_string($val) ? (preg_match('/[A-Za-z]/',$val)?0:1) : 0;
    if($newScore>$existingScore){ $canon[$ck]=$val; }
  }
  ksort($canon);
  $out[$lc]=$canon;
}

function exportArray(array $arr): string {
  $s="<?php\n\nreturn [\n";
  foreach($arr as $k=>$v){
    $ks=str_replace(["\\","'"],["\\\\","\\'"],(string)$k);
    if(is_string($v)){
      $vs=str_replace(["\\","'"],["\\\\","\\'"],$v);
      $s.="    '{$ks}' => '{$vs}',\n";
    } else {
      $s.="    '{$ks}' => ".var_export($v,true).",\n";
    }
  }
  return $s."];\n";
}

foreach($files as $lc=>$path){ file_put_contents($path, exportArray($out[$lc])); }

// export legacy map
ksort($legacyMap);
$mapPhp="<?php\n\nreturn [\n";
foreach($legacyMap as $k=>$v){
  $ks=str_replace(["\\","'"],["\\\\","\\'"],$k);
  $vs=str_replace(["\\","'"],["\\\\","\\'"],$v);
  $mapPhp.="    '{$ks}' => '{$vs}',\n";
}
$mapPhp.= "];\n";
file_put_contents('config/ui_legacy_map.php',$mapPhp);

echo 'canonical_keys='.count($out['en'])."\n";
echo 'legacy_map='.count($legacyMap)."\n";
