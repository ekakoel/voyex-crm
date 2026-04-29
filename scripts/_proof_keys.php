<?php
$en=include 'lang/en/ui_core.php';
$s=include 'lang/zh_Hans/ui_core.php';
$t=include 'lang/zh_Hant/ui_core.php';
$keys=['Browse and manage data.','page title','page subtitle','create page title','edit page title','show page title','Search','search','action','Add Item','You can add new items (Attraction, Activity, Inter Island Transfer, or F&B) to this itinerary.'];
foreach($keys as $k){echo $k,"\n"; echo ' en: '.($en[$k]??'-')."\n"; echo ' hs: '.($s[$k]??'-')."\n"; echo ' ht: '.($t[$k]??'-')."\n\n";}
