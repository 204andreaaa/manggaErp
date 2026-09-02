<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=mandau_db', 'root', '');
$pdo->exec('SET FOREIGN_KEY_CHECKS=0');
$pdo->exec('DROP TABLE IF EXISTS erp_budget_ledgers, erp_work_items, erp_sub_projects, erp_master_projects, erp_budget_parents');
$pdo->exec("DELETE FROM migrations WHERE migration LIKE '%erp_budget%' OR migration LIKE '%erp_sub_project%' OR migration LIKE '%erp_work_item%' OR migration LIKE '%erp_master_project%'");
$pdo->exec('SET FOREIGN_KEY_CHECKS=1');
echo "Deleted from mandau_db!\n";
