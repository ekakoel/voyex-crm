<?php
$repl = [
  'modules itineraries messages created' => 'Itinerary created successfully.',
  'modules itineraries messages unauthenticated' => 'You must be signed in.',
  'modules itineraries messages duplicate in progress' => 'Duplicate process is already in progress.',
  'modules itineraries source itinerary' => 'Source itinerary',
  'modules itineraries messages duplicated' => 'Itinerary duplicated successfully.',
  'modules itineraries messages final cannot edit' => 'Final itinerary cannot be edited.',
  'modules itineraries messages locked by quotation edit' => 'Itinerary is locked by quotation and cannot be edited.',
  'modules itineraries messages manual item not in queue' => 'Manual item is not in validation queue.',
  'modules itineraries messages manual item already validated' => 'Manual item is already validated.',
  'modules itineraries messages manual item validated' => 'Manual item validated successfully.',
  'modules itineraries messages locked by quotation update' => 'Itinerary is locked by quotation and cannot be updated.',
  'modules itineraries messages updated and synced' => 'Itinerary updated and synced successfully.',
  'modules itineraries messages updated' => 'Itinerary updated successfully.',
  'modules itineraries messages final cannot delete' => 'Final itinerary cannot be deleted.',
  'modules itineraries reason quotation approved final' => 'Related quotation is approved/final.',
  'modules itineraries messages deactivated' => 'Itinerary deactivated successfully.',
  'modules itineraries messages final status locked' => 'Final itinerary status is locked.',
  'modules itineraries messages activated' => 'Itinerary activated successfully.',
  'modules itineraries messages permission denied' => 'Permission denied.',
  'modules inquiries messages created' => 'Inquiry created successfully.',
  'modules inquiries messages locked by quotation edit' => 'Inquiry is locked by quotation and cannot be edited.',
  'modules inquiries messages locked by quotation update' => 'Inquiry is locked by quotation and cannot be updated.',
  'modules inquiries messages updated' => 'Inquiry updated successfully.',
  'modules inquiries messages deactivated' => 'Inquiry deactivated successfully.',
  'modules inquiries messages activated' => 'Inquiry activated successfully.',
  'modules inquiries messages followup added' => 'Follow-up added successfully.',
  'modules inquiries messages followup done' => 'Follow-up marked as done.',
  'modules inquiries messages communication added' => 'Communication added successfully.',
  'modules inquiries messages no permission reset reminder' => 'You do not have permission to reset reminder.',
  'modules inquiries messages reminder reset' => 'Reminder reset successfully.',
  'modules inquiries messages no permission modify' => 'You do not have permission to modify this inquiry.',
];
$files=[__DIR__.'/../app/Http/Controllers/Admin/ItineraryController.php',__DIR__.'/../app/Http/Controllers/Sales/InquiryController.php'];
foreach($files as $f){
  $s=file_get_contents($f);
  foreach($repl as $from=>$to){
    $s=str_replace("ui_phrase('{$from}')","ui_phrase('{$to}')",$s);
  }
  file_put_contents($f,$s);
}
echo "done\n";
