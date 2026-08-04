<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Erp\ErpProduct;
use App\Models\Erp\ProductFamily;
use App\Models\Erp\ProductType;
use App\Models\Erp\Brand;
use App\Models\Erp\ProductModel;
use App\Models\Erp\Currency;
use App\Models\Erp\Uom;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class ErpProductController extends Controller
{
    private string $codePrefix = 'EPRD-';

    public function index()
    {
        abort_unless(auth()->user()->hasPermission('products.view'), 403);

        $uoms     = Uom::select('id', 'uom_name')->orderBy('uom_name')->get();
        $families = ProductFamily::select('id', 'family_name')->orderBy('family_name')->get();
        $types    = ProductType::select('id', 'type_name')->orderBy('type_name')->get();
        $brands   = Brand::select('id', 'brand_name')->orderBy('brand_name')->get();
        $models   = ProductModel::select('id', 'model_name')->orderBy('model_name')->get();
        $currencies = Currency::select('id', 'code', 'name', 'symbol')->orderBy('code')->get();

        $nextProductCode = $this->generateNextCode();

        return view('erp.products.index', compact(
            'uoms',
            'families',
            'types',
            'brands',
            'models',
            'currencies',
            'nextProductCode'
        ));
    }

    public function datatable(Request $r)
    {
        abort_unless(auth()->user()->hasPermission('products.view'), 403);

        if (\Illuminate\Support\Facades\Schema::hasTable('erp_products') && !\Illuminate\Support\Facades\Schema::hasColumn('erp_products', 'image')) {
            \Illuminate\Support\Facades\Schema::table('erp_products', function ($table) {
                $table->string('image')->nullable()->after('name');
            });
        }

        $draw        = (int) $r->input('draw', 1);
        $start       = (int) $r->input('start', 0);
        $length      = (int) $r->input('length', 10);
        $orderColIdx = (int) $r->input('order.0.column', 0);
        $orderDir    = $r->input('order.0.dir', 'asc') === 'desc' ? 'desc' : 'asc';
        $search      = trim((string) $r->input('search.value', ''));

        $query = ErpProduct::query()
            ->with(['uom', 'productFamily', 'productType', 'brand', 'productModel', 'currency']);

        $recordsTotal = (clone $query)->count();

        if ($search !== '') {
            $query->where(function($q) use ($search){
                $q->where('product_code', 'like', "%{$search}%")
                  ->orWhere('part_number', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $recordsFiltered = (clone $query)->count();

        // Ordering mapping
        $columnsMap = [
            0 => 'id',
            1 => 'product_code',
            2 => 'part_number',
            3 => 'name',
            4 => 'buying_price',
            5 => 'is_active',
        ];
        $orderCol = $columnsMap[$orderColIdx] ?? 'product_code';

        $rows = $query->orderBy($orderCol, $orderDir)
            ->skip($start)->take($length)->get()
            ->map(function ($p, $i) use ($start) {
                $statusBadge = $p->is_active
                    ? '<span class="badge badge-status-active">ACTIVE</span>'
                    : '<span class="badge badge-status-inactive">INACTIVE</span>';

                $imageUrl = $p->image_url ?: '';

                $editBtn = auth()->user()->hasPermission('products.update')
                    ? sprintf(
                        '<button class="action-btn-custom action-btn-edit me-1" title="Edit" onclick="openEdit(%d,\'%s\',\'%s\',\'%s\',\'%s\',%s,%s,%s,%s,%s,%s,%s,%d,%d,\'%s\')"><i class="bx bx-edit-alt"></i></button>',
                        $p->id,
                        addslashes(e($p->product_code)),
                        addslashes(e($p->part_number ?? '')),
                        addslashes(e($p->name)),
                        addslashes(e($p->description ?? '')),
                        $p->uom_id ?? 'null',
                        $p->buying_price,
                        $p->product_family_id ?? 'null',
                        $p->product_type_id ?? 'null',
                        $p->brand_id ?? 'null',
                        $p->product_model_id ?? 'null',
                        $p->currency_id ?? 'null',
                        $p->is_active ? 1 : 0,
                        $p->is_physical ? 1 : 0,
                        addslashes($imageUrl)
                    )
                    : '';

                $deleteBtn = auth()->user()->hasPermission('products.delete')
                    ? '<button class="action-btn-custom action-btn-delete" title="Hapus" onclick="deleteItem('.$p->id.')"><i class="bx bx-trash"></i></button>'
                    : '';

                $symbol = $p->currency?->symbol ?: ($p->currency?->code ?: 'Rp');
                $priceFormatted = '<span class="fw-bold text-dark" style="font-size:14px;">' . $symbol . ' ' . number_format($p->buying_price, 0, ',', '.') . '</span>';
                
                $itemTypeBadge = $p->is_physical
                    ? '<span class="badge bg-label-primary px-2 py-1"><i class="bx bx-package me-1"></i>Fisik</span>'
                    : '<span class="badge bg-label-warning px-2 py-1"><i class="bx bx-wrench me-1"></i>Jasa</span>';

                // Image & Product Title Cell
                if ($imageUrl) {
                    $imgHtml = sprintf(
                        '<img src="%s" alt="%s" class="prod-img-box me-3 clickable-img" onclick="openImagePreview(\'%s\', \'%s\')" title="Klik untuk memperbesar">',
                        e($imageUrl),
                        e($p->name),
                        addslashes(e($imageUrl)),
                        addslashes(e($p->name))
                    );
                } else {
                    $imgHtml = '<div class="prod-img-placeholder me-3"><i class="bx bx-package fs-3"></i></div>';
                }

                $subDetail = [];
                if ($p->part_number) $subDetail[] = 'PN: ' . e($p->part_number);
                if ($p->description) $subDetail[] = e($p->description);
                $subDetailText = !empty($subDetail) ? implode(' • ', $subDetail) : 'No description';

                $productCell = sprintf(
                    '<div class="d-flex align-items-center">%s<div><div class="prod-title">%s</div><div class="prod-desc">%s</div></div></div>',
                    $imgHtml,
                    e($p->name),
                    $subDetailText
                );

                // Category Cell
                $catName = e($p->productFamily?->family_name ?: ($p->brand?->brand_name ?: 'General'));
                $categoryCell = sprintf(
                    '<div class="category-badge-wrapper"><div class="category-icon-circle"><i class="bx bx-category"></i></div><span class="fw-semibold text-secondary" style="font-size:13px;">%s</span></div>',
                    $catName
                );

                // Code / SKU Cell
                $codeCell = '<code class="fw-bold text-primary px-2 py-1 bg-light border rounded" style="font-size:12px;">' . e($p->product_code) . '</code>';

                return [
                    'rownum'       => $start + $i + 1,
                    'id'           => $p->id,
                    'product_code' => $codeCell,
                    'part_number'  => e($p->part_number ?? '-'),
                    'product'      => $productCell,
                    'category'     => $categoryCell,
                    'item_type'    => $itemTypeBadge,
                    'uom'          => '<span class="badge bg-light text-dark border fw-normal">' . e($p->uom?->uom_name ?? '-') . '</span>',
                    'family'       => e($p->productFamily?->family_name ?? '-'),
                    'type'         => e($p->productType?->type_name ?? '-'),
                    'brand'        => e($p->brand?->brand_name ?? '-'),
                    'model'        => e($p->productModel?->model_name ?? '-'),
                    'buying_price' => $priceFormatted,
                    'is_active'    => $statusBadge,
                    'actions'      => '<div class="text-center">'.$editBtn.$deleteBtn.'</div>',
                ];
            });

        return response()->json([
            'draw'            => $draw,
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $rows,
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('products.create'), 403);

        $code = strtoupper(trim((string)$request->input('product_code', '')));
        if ($code === '') $code = $this->generateNextCode();
        $request->merge(['product_code' => $code]);

        foreach (['uom_id', 'product_family_id', 'product_type_id', 'brand_id', 'product_model_id', 'currency_id'] as $fk) {
            if ($request->input($fk) === '' || $request->input($fk) === 'null' || $request->input($fk) === 'undefined') {
                $request->merge([$fk => null]);
            }
        }

        if (!$request->hasFile('image')) {
            $request->offsetUnset('image');
        }

        $data = $request->validate([
            'product_code'      => ['required', 'max:50', Rule::unique('erp_products', 'product_code')->whereNull('deleted_at')],
            'part_number'       => ['nullable', 'max:100'],
            'name'              => ['required', 'max:150'],
            'image'             => ['nullable', 'file', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:3072'],
            'description'       => ['nullable', 'string'],
            'uom_id'            => ['nullable', 'exists:uoms,id'],
            'buying_price'      => ['required', 'numeric', 'min:0'],
            'product_family_id' => ['nullable', 'exists:product_families,id'],
            'product_type_id'   => ['nullable', 'exists:product_types,id'],
            'brand_id'          => ['nullable', 'exists:brands,id'],
            'product_model_id'  => ['nullable', 'exists:product_models,id'],
            'currency_id'       => ['nullable', 'exists:currencies,id'],
            'is_physical'       => ['nullable', 'boolean'],
            'is_active'         => ['nullable', 'boolean'],
        ]);

        $data['is_physical'] = $request->boolean('is_physical', true);
        $data['is_active'] = $request->boolean('is_active', true);

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = 'product_' . time() . '_' . Str::random(6) . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/products'), $filename);
            $data['image'] = 'uploads/products/' . $filename;
        }

        ErpProduct::create($data);

        return response()->json(['success' => 'ERP Product created successfully.']);
    }

    public function update(Request $request, $id)
    {
        abort_unless(auth()->user()->hasPermission('products.update'), 403);
        $product = ErpProduct::findOrFail($id);

        $code = strtoupper(trim((string)$request->input('product_code', '')));
        if ($code === '') $code = $product->product_code;
        $request->merge(['product_code' => $code]);

        foreach (['uom_id', 'product_family_id', 'product_type_id', 'brand_id', 'product_model_id', 'currency_id'] as $fk) {
            if ($request->input($fk) === '' || $request->input($fk) === 'null' || $request->input($fk) === 'undefined') {
                $request->merge([$fk => null]);
            }
        }

        if (!$request->hasFile('image')) {
            $request->offsetUnset('image');
        }

        $data = $request->validate([
            'product_code'      => ['required', 'max:50', Rule::unique('erp_products', 'product_code')->ignore($product->id)->whereNull('deleted_at')],
            'part_number'       => ['nullable', 'max:100'],
            'name'              => ['required', 'max:150'],
            'image'             => ['nullable', 'file', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:3072'],
            'description'       => ['nullable', 'string'],
            'uom_id'            => ['nullable', 'exists:uoms,id'],
            'buying_price'      => ['required', 'numeric', 'min:0'],
            'product_family_id' => ['nullable', 'exists:product_families,id'],
            'product_type_id'   => ['nullable', 'exists:product_types,id'],
            'brand_id'          => ['nullable', 'exists:brands,id'],
            'product_model_id'  => ['nullable', 'exists:product_models,id'],
            'currency_id'       => ['nullable', 'exists:currencies,id'],
            'is_physical'       => ['nullable', 'boolean'],
            'is_active'         => ['nullable', 'boolean'],
        ]);

        $data['is_physical'] = $request->boolean('is_physical', true);
        $data['is_active'] = $request->boolean('is_active');

        if ($request->hasFile('image')) {
            if ($product->image && file_exists(public_path($product->image))) {
                @unlink(public_path($product->image));
            }
            $file = $request->file('image');
            $filename = 'product_' . time() . '_' . Str::random(6) . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/products'), $filename);
            $data['image'] = 'uploads/products/' . $filename;
        }

        $product->update($data);

        return response()->json(['success' => 'ERP Product updated successfully.']);
    }

    public function destroy($id)
    {
        abort_unless(auth()->user()->hasPermission('products.delete'), 403);
        ErpProduct::findOrFail($id)->delete();

        return response()->json(['success' => 'ERP Product deleted successfully.']);
    }

    public function exportExcel(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('products.export'), 403);

        $products = ErpProduct::with(['uom', 'family', 'type', 'brand', 'model', 'currency'])->orderBy('id')->get();

        $filename = 'products_export_' . date('Ymd_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($products) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Code', 'Part No', 'Name', 'UOM', 'Brand', 'Price', 'Status']);

            foreach ($products as $p) {
                fputcsv($file, [
                    $p->id,
                    $p->product_code,
                    $p->part_number ?? '-',
                    $p->name,
                    $p->uom->code ?? '-',
                    $p->brand->name ?? '-',
                    $p->price ?? 0,
                    $p->is_active ? 'Active' : 'Inactive',
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function nextCode()
    {
        abort_unless(auth()->user()->hasPermission('products.create'), 403);
        return response()->json(['next_code' => $this->generateNextCode()]);
    }

    private function generateNextCode(): string
    {
        $prefix = $this->codePrefix;
        $latest = ErpProduct::withTrashed()
            ->where('product_code', 'like', $prefix.'%')
            ->orderByRaw('CAST(SUBSTRING(product_code, '.(strlen($prefix)+1).') AS UNSIGNED) DESC')
            ->value('product_code');

        $num = 0;
        if ($latest && preg_match('/^'.preg_quote($prefix, '/').'(\d+)$/i', $latest, $m)) {
            $num = (int)$m[1];
        }

        return $prefix . str_pad($num + 1, 3, '0', STR_PAD_LEFT);
    }
}
