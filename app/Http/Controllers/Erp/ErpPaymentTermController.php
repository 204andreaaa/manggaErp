<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Erp\ErpPaymentTerm;
use Illuminate\Http\Request;

class ErpPaymentTermController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->hasRole(['procurement', 'finance', 'superadmin', 'ceo', 'admin_project']) || auth()->user()->hasPermission('supplier.view'), 403);
        $terms = ErpPaymentTerm::orderBy('name')->get();
        return view('erp.payment_terms.index', compact('terms'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->hasRole(['procurement', 'finance', 'superadmin']) || auth()->user()->hasPermission('supplier.create'), 403);
        
        $data = $request->validate([
            'name' => 'required|string|max:150|unique:erp_payment_terms,name',
            'term_schedule' => 'required|json'
        ]);

        // Validate JSON structure
        $schedule = json_decode($data['term_schedule'], true);
        if (!is_array($schedule)) {
            return redirect()->back()->with('error', 'Term schedule must be a valid JSON array.');
        }

        $total = 0;
        foreach ($schedule as $term) {
            if (!isset($term['name']) || !isset($term['percentage'])) {
                return redirect()->back()->with('error', 'Each term must have a name and percentage.');
            }
            $total += (float)$term['percentage'];
        }

        if (round($total, 2) != 100) {
            return redirect()->back()->with('error', "Total percentage must be exactly 100%. Current total is {$total}%.");
        }

        ErpPaymentTerm::create([
            'name' => $data['name'],
            'is_active' => true,
            'term_schedule' => $schedule
        ]);

        return redirect()->back()->with('success', 'Payment Term created successfully.');
    }

    public function update(Request $request, ErpPaymentTerm $paymentTerm)
    {
        abort_unless(auth()->user()->hasRole(['procurement', 'finance', 'superadmin']) || auth()->user()->hasPermission('supplier.create'), 403);
        
        $data = $request->validate([
            'name' => 'required|string|max:150|unique:erp_payment_terms,name,' . $paymentTerm->id,
            'term_schedule' => 'required|json',
            'is_active' => 'boolean'
        ]);

        $schedule = json_decode($data['term_schedule'], true);
        if (!is_array($schedule)) {
            return redirect()->back()->with('error', 'Term schedule must be a valid JSON array.');
        }

        $total = 0;
        foreach ($schedule as $term) {
            if (!isset($term['name']) || !isset($term['percentage'])) {
                return redirect()->back()->with('error', 'Each term must have a name and percentage.');
            }
            $total += (float)$term['percentage'];
        }

        if (round($total, 2) != 100) {
            return redirect()->back()->with('error', "Total percentage must be exactly 100%. Current total is {$total}%.");
        }

        $paymentTerm->update([
            'name' => $data['name'],
            'is_active' => $request->has('is_active'),
            'term_schedule' => $schedule
        ]);

        return redirect()->back()->with('success', 'Payment Term updated successfully.');
    }
    
    public function destroy(ErpPaymentTerm $paymentTerm)
    {
        abort_unless(auth()->user()->hasRole(['procurement', 'finance', 'superadmin']) || auth()->user()->hasPermission('supplier.create'), 403);
        $paymentTerm->delete();
        return redirect()->back()->with('success', 'Payment Term deleted successfully.');
    }
}