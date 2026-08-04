<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Erp\ErpPaymentTerm;
use Illuminate\Http\Request;

class ErpPaymentTermController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->hasPermission('supplier.view'), 403);

        $activeTerms = ErpPaymentTerm::where('is_active', true)->pluck('name')->join("\n");

        return view('erp.payment_terms.index', compact('activeTerms'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('supplier.create'), 403);

        $text = trim((string) $request->input('values', ''));
        $lines = array_filter(array_map('trim', explode("\n", $text)));

        // Synchronize terms:
        // Set all terms not in the list to inactive
        ErpPaymentTerm::whereNotIn('name', $lines)->update(['is_active' => false]);

        // Activate or create terms in the list
        foreach ($lines as $line) {
            ErpPaymentTerm::updateOrCreate(
                ['name' => $line],
                ['is_active' => true]
            );
        }

        return redirect()->back()->with('success', 'Payment Terms updated successfully.');
    }
}
