<?php
$rows = DB::connection('tenant')->table('request_form_items')->orderBy('id', 'desc')->take(5)->get();
echo json_encode($rows, JSON_PRETTY_PRINT);
