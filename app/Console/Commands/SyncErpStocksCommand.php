<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Project;
use App\Services\ErpTenantManager;
use App\Models\Erp\ErpGoodsReceipt;
use App\Models\Erp\ErpStock;
use Illuminate\Support\Facades\DB;

class SyncErpStocksCommand extends Command
{
    protected $signature = 'erp:sync-stocks';
    protected $description = 'Sync all existing Received Goods Receipts (DOs) to Inventory Stocks across all tenant projects';

    public function handle()
    {
        $projects = Project::where('is_active', true)->get();

        if ($projects->isEmpty()) {
            $this->info('No active projects found.');
            return self::SUCCESS;
        }

        foreach ($projects as $project) {
            $this->info("==========================================");
            $this->info("Syncing stocks for Project: {$project->name} ({$project->db_name})");
            $this->info("==========================================");

            try {
                ErpTenantManager::switchToProject($project);

                // Clear or recalculate erp_stocks based on Received Goods Receipts
                ErpStock::truncate();

                $receivedGrs = ErpGoodsReceipt::with(['items.requestFormItem.erpProduct', 'purchaseOrder'])
                    ->where('status', 'Received')
                    ->get();

                $this->info("Found " . $receivedGrs->count() . " Received Goods Receipts.");

                $syncedCount = 0;
                foreach ($receivedGrs as $gr) {
                    $po = $gr->purchaseOrder;
                    if ($po && (!$po->gr || $po->status !== 'Completed')) {
                        $po->update(['gr' => true, 'status' => 'Completed']);
                        $this->line("  -> Updated PO {$po->po_no} status to Completed & GR checked.");
                    }

                    $warehouseId = $gr->warehouse_id ?: $gr->purchaseOrder?->erp_warehouse_id;

                    if (!$warehouseId) {
                        // Fallback to first available warehouse
                        $warehouseId = DB::table('erp_warehouses')->value('id');
                    }

                    if (!$warehouseId) {
                        $this->warn("Skipping GR {$gr->do_no}: No warehouse ID found.");
                        continue;
                    }

                    foreach ($gr->items as $grItem) {
                        $product = $grItem->requestFormItem?->erpProduct;
                        
                        // Default is_physical to true if null
                        $isPhysical = $product ? ($product->is_physical ?? true) : false;

                        if ($product && $isPhysical && $grItem->received_qty > 0) {
                            $supplierId = $po?->supplier_id;
                            $stock = ErpStock::firstOrCreate(
                                [
                                    'erp_product_id' => $product->id,
                                    'erp_warehouse_id' => $warehouseId,
                                    'erp_supplier_id' => $supplierId,
                                ],
                                ['qty_on_hand' => 0]
                            );
                            
                            $stock->increment('qty_on_hand', $grItem->received_qty);
                            $syncedCount++;
                            $this->line("  -> Added {$grItem->received_qty} {$product->name} to Warehouse ID {$warehouseId}");
                        }
                    }
                }

                $this->info("Successfully synced {$syncedCount} item stock records for {$project->name}.");
            } catch (\Throwable $e) {
                $this->error("Error syncing stocks for {$project->name}: " . $e->getMessage());
            }
        }

        $this->info("\nAll tenant inventory stocks synced successfully!");
        return self::SUCCESS;
    }
}
