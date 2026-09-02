<?php
$content = file_get_contents('app/Http/Controllers/Erp/ErpPurchaseOrderController.php');
preg_match_all('/public function ([a-zA-Z0-9_]+)/', $content, $matches);
echo implode(', ', $matches[1]);
