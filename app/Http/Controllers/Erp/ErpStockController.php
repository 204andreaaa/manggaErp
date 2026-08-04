<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Erp\ErpStock;
use App\Models\Erp\ErpProduct;
use App\Models\Erp\ErpWarehouse;
use Illuminate\Http\Request;

class ErpStockController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->check()) abort(401);

        $warehouses = ErpWarehouse::where('is_active', true)->orderBy('name')->get();
        $products = ErpProduct::where('is_active', true)->orderBy('name')->get();

        return view('erp.stocks.index', compact('warehouses', 'products'));
    }

    public function datatable(Request $r)
    {
        if (!auth()->check()) abort(401);

        try {
            $draw        = (int) $r->input('draw', 1);
            $start       = (int) $r->input('start', 0);
            $length      = (int) $r->input('length', 10);
            $search      = trim((string) $r->input('search.value', ''));
            $warehouseId = $r->input('warehouse_id');
            $itemType    = $r->input('item_type'); // 'physical', 'non_physical', or ''

            $query = ErpStock::query()
                ->with(['erpProduct.uom', 'erpProduct.brand', 'erpProduct.productModel', 'warehouse']);

            if ($warehouseId) {
                $query->where('erp_warehouse_id', $warehouseId);
            }

            if ($itemType === 'physical') {
                $query->whereHas('erpProduct', fn($q) => $q->where('is_physical', true));
            } elseif ($itemType === 'non_physical') {
                $query->whereHas('erpProduct', fn($q) => $q->where('is_physical', false));
            }

            if ($search !== '') {
                $query->where(function ($sub) use ($search) {
                    $sub->whereHas('erpProduct', function ($q) use ($search) {
                        $q->where('product_code', 'like', "%{$search}%")
                          ->orWhere('name', 'like', "%{$search}%")
                          ->orWhere('part_number', 'like', "%{$search}%");
                    })->orWhereHas('warehouse', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                          ->orWhere('code', 'like', "%{$search}%");
                    });
                });
            }

            $recordsTotal    = ErpStock::count();
            $recordsFiltered = (clone $query)->count();

            $rows = $query->orderBy('qty_on_hand', 'desc')
                ->skip($start)->take($length)->get()
                ->map(function ($s, $i) use ($start) {
                    $p = $s->erpProduct;
                    $wh = $s->warehouse;

                    $typeBadge = ($p && $p->is_physical)
                        ? '<span class="badge bg-label-primary"><i class="bx bx-package me-1"></i>Fisik</span>'
                        : '<span class="badge bg-label-warning"><i class="bx bx-wrench me-1"></i>Non-Fisik / Jasa</span>';

                    $stockBadge = $s->qty_on_hand > 10
                        ? '<span class="badge bg-label-success fw-bold">In Stock</span>'
                        : ($s->qty_on_hand > 0 
                            ? '<span class="badge bg-label-warning fw-bold">Low Stock</span>' 
                            : '<span class="badge bg-label-danger fw-bold">Out of Stock</span>');

                    return [
                        'rownum'       => $start + $i + 1,
                        'product_code' => e($p?->product_code ?? '-'),
                        'product_name' => e($p?->name ?? '-'),
                        'part_number'  => e($p?->part_number ?? '-'),
                        'item_type'    => $typeBadge,
                        'warehouse'    => e($wh?->name ?? '-'),
                        'qty_on_hand'  => number_format($s->qty_on_hand, 2, ',', '.') . ' ' . e($p?->uom?->uom_name ?? ''),
                        'stock_status' => $stockBadge,
                        'updated_at'   => $s->updated_at?->format('Y-m-d H:i') ?? '-',
                    ];
                });

            return response()->json([
                'draw'            => $draw,
                'recordsTotal'    => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'data'            => $rows,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('ErpStockController datatable error: ' . $e->getMessage());
            return response()->json([
                'draw'            => (int) $r->input('draw', 1),
                'recordsTotal'    => 0,
                'recordsFiltered' => 0,
                'data'            => [],
            ]);
        }
    }
}
