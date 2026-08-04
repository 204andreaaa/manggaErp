<?php
$rows = DB::connection('tenant')->table('request_form_items')->where('status', 'Ordered')->get();
foreach ($rows as $r) {
    echo $r->rf_detail_no . " \n";
}
DB::connection('tenant')->table('request_form_items')->where('status', 'Ordered')->update(['status' => 'Completed']);
echo "Updated all Ordered items to Completed";
