<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Erp\ErpSubProject;
use App\Models\Erp\ErpSupplier;
use App\Models\Erp\RequestFormItem;
use App\Models\Erp\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Company vehicles (Own / Rent), like the Salesforce "Vehicle" object. Costs are not
 * entered here: they come from RF lines that name the vehicle, once their PO is approved.
 */
class VehicleController extends Controller
{
    private function authorizeTo(string $action): void
    {
        abort_unless(auth()->user()->hasPermission("vehicles.{$action}"), 403);
    }

    public function index(Request $request)
    {
        $this->authorizeTo('view');

        $vehicles = Vehicle::with(['rentalSupplier:id,name', 'subProject:id,sub_project_code,name'])
            ->when(in_array($request->query('ownership'), Vehicle::OWNERSHIPS, true), fn ($q) => $q->where('ownership', $request->query('ownership')))
            ->when($request->query('active', '1') !== 'all', fn ($q) => $q->where('is_active', $request->query('active', '1') === '1'))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->query('q').'%';
                $q->where(fn ($w) => $w->where('plate_number', 'like', $term)->orWhere('brand', 'like', $term)
                    ->orWhere('model', 'like', $term)->orWhere('location', 'like', $term)->orWhere('pic', 'like', $term)
                    ->orWhere('replacement_plate', 'like', $term));
            })
            ->orderBy('plate_number')
            ->get();

        // the rent end date can move forward through approved rent POs, so it is worked out per vehicle
        $vehicles->each(function (Vehicle $v) {
            $v->rent_end = $v->effectiveRentalEndDate();
            $v->rent_status = $v->rentStatus($v->rent_end);
        });

        if ($request->query('alert') === 'expired') {
            $vehicles = $vehicles->where('rent_status', 'Expired')->values();
        } elseif ($request->query('alert') === 'documents') {
            $vehicles = $vehicles->filter(fn ($v) => in_array(Vehicle::expiryState($v->stnk_expiry_date), ['expired', 'soon'], true)
                || in_array(Vehicle::expiryState($v->tax_expiry_date), ['expired', 'soon'], true))->values();
        }

        $active = Vehicle::where('is_active', true)->get();
        $active->each(fn (Vehicle $v) => $v->rent_status = $v->rentStatus());

        return view('erp.vehicles.index', [
            'vehicles' => $vehicles,
            'stats' => [
                'active' => $active->count(),
                'own' => $active->where('ownership', 'Own')->count(),
                'rent' => $active->where('ownership', 'Rent')->count(),
                'expired' => $active->where('rent_status', 'Expired')->count(),
                'documents' => $active->filter(fn ($v) => in_array(Vehicle::expiryState($v->stnk_expiry_date), ['expired', 'soon'], true)
                    || in_array(Vehicle::expiryState($v->tax_expiry_date), ['expired', 'soon'], true))->count(),
            ],
        ]);
    }

    public function create(Request $request)
    {
        $this->authorizeTo('create');
        $ownership = in_array($request->query('ownership'), Vehicle::OWNERSHIPS, true) ? $request->query('ownership') : null;
        if (!$ownership) {
            return view('erp.vehicles.choose_type');
        }

        return view('erp.vehicles.form', $this->formData(new Vehicle([
            'ownership' => $ownership, 'kind' => 'Mobil', 'is_active' => true,
            'purchase_date' => $ownership === 'Own' ? now() : null,
            'rental_start_date' => $ownership === 'Rent' ? now() : null,
        ])));
    }

    public function store(Request $request)
    {
        $this->authorizeTo('create');
        $data = $this->validated($request);

        $vehicle = Vehicle::create($data + ['created_by_id' => auth()->id(), 'updated_by_id' => auth()->id()]);

        if ($request->boolean('save_new')) {
            return redirect()->route('erp.vehicles.create', ['ownership' => $vehicle->ownership])
                ->with('success', "Kendaraan {$vehicle->plate_number} disimpan. Silakan isi kendaraan berikutnya.");
        }

        return redirect()->route('erp.vehicles.show', $vehicle)->with('success', "Kendaraan {$vehicle->plate_number} disimpan.");
    }

    public function show(Vehicle $vehicle)
    {
        $this->authorizeTo('view');
        $vehicle->load(['rentalSupplier', 'subProject', 'createdBy']);

        $lines = $vehicle->costLines();
        // RF lines for this vehicle that have no approved PO yet (still in progress)
        $inProgress = RequestFormItem::with('requestForm')
            ->where('vehicle_id', $vehicle->id)
            ->where('status', '!=', 'Cancelled')
            ->whereDoesntHave('purchaseOrderItems.purchaseOrder', fn ($q) => $q->whereIn('status', Vehicle::COUNTED_PO_STATUSES))
            ->whereHas('requestForm', fn ($q) => $q->whereNotIn('status', ['Rejected']))
            ->latest('id')->get();

        $rentEnd = $vehicle->effectiveRentalEndDate();

        return view('erp.vehicles.show', [
            'vehicle' => $vehicle,
            'lines' => $lines,
            'inProgress' => $inProgress,
            'totals' => collect(Vehicle::COST_TYPES)->mapWithKeys(fn ($type) => [
                $type => (float) $lines->filter(fn ($l) => $l->requestFormItem?->vehicle_cost_type === $type)->sum('total_cost'),
            ]),
            'rentEnd' => $rentEnd,
            'rentStatus' => $vehicle->rentStatus($rentEnd),
        ]);
    }

    public function edit(Vehicle $vehicle)
    {
        $this->authorizeTo('update');

        return view('erp.vehicles.form', $this->formData($vehicle));
    }

    public function update(Request $request, Vehicle $vehicle)
    {
        $this->authorizeTo('update');
        $vehicle->update($this->validated($request, $vehicle) + ['updated_by_id' => auth()->id()]);

        return redirect()->route('erp.vehicles.show', $vehicle)->with('success', 'Data kendaraan diperbarui.');
    }

    public function destroy(Vehicle $vehicle)
    {
        $this->authorizeTo('delete');

        // its RF / PO history must keep pointing at it; a vehicle that is gone is set inactive instead
        if ($vehicle->requestFormItems()->exists()) {
            return redirect()->back()->with('error', "Kendaraan {$vehicle->plate_number} sudah dipakai di RF, jadi tidak bisa dihapus. Nonaktifkan saja (hilangkan centang Aktif).");
        }

        $vehicle->delete();

        return redirect()->route('erp.vehicles.index')->with('success', "Kendaraan {$vehicle->plate_number} dihapus.");
    }

    /**
     * Required fields follow Salesforce: an Own vehicle needs its plate, year and purchase
     * price; a Rent vehicle needs nearly everything, including the supplier and the rent period.
     */
    private function validated(Request $request, ?Vehicle $vehicle = null): array
    {
        $ownership = $vehicle?->ownership ?? $request->input('ownership');
        $rent = $ownership === 'Rent';
        $requiredIfRent = $rent ? 'required' : 'nullable';

        $data = $request->validate([
            'ownership' => [$vehicle ? 'nullable' : 'required', Rule::in(Vehicle::OWNERSHIPS)],
            'plate_number' => ['required', 'string', 'max:20',
                Rule::unique('tenant.erp_vehicles', 'plate_number')->ignore($vehicle?->id)->whereNull('deleted_at')],
            'company_id' => [$requiredIfRent, 'nullable', 'integer', Rule::exists('master.companies', 'id')],
            'kind' => ['required', Rule::in(Vehicle::KINDS)],
            'brand' => [$requiredIfRent, 'nullable', 'string', 'max:60'],
            'model' => ['nullable', 'string', 'max:60'],
            'vehicle_type' => ['nullable', 'string', 'max:60'],
            'production_year' => ['required', 'integer', 'min:1950', 'max:'.(now()->year + 1)],
            'color' => ['nullable', 'string', 'max:40'],
            'condition' => [$requiredIfRent, 'nullable', 'string', 'max:40'],
            'usage_status' => ['nullable', 'string', 'max:40'],
            'region' => [$requiredIfRent, 'nullable', 'string', 'max:80'],
            'location' => [$requiredIfRent, 'nullable', 'string', 'max:80'],
            'current_area' => [$requiredIfRent, 'nullable', 'string', 'max:80'],
            'sub_project_id' => ['nullable', 'integer', Rule::exists('tenant.erp_sub_projects', 'id')],
            'pic' => ['nullable', 'string', 'max:120'],
            'stnk_name' => ['nullable', 'string', 'max:150'],
            'stnk_address' => ['nullable', 'string', 'max:500'],
            'chassis_number' => ['nullable', 'string', 'max:60'],
            'engine_number' => ['nullable', 'string', 'max:60'],
            'stnk_expiry_date' => [$requiredIfRent, 'nullable', 'date'],
            'tax_expiry_date' => ['nullable', 'date'],
            'purchase_date' => ['nullable', 'date'],
            'purchase_price' => [$rent ? 'nullable' : 'required', 'numeric', 'min:0', 'max:999999999999'],
            'rental_supplier_id' => [$requiredIfRent, 'nullable', 'integer', Rule::exists('tenant.erp_suppliers', 'id')],
            'monthly_rent' => [$requiredIfRent, 'nullable', 'numeric', 'min:0', 'max:999999999999'],
            'rental_start_date' => [$requiredIfRent, 'nullable', 'date'],
            'rental_end_date' => [$requiredIfRent, 'nullable', 'date', 'after_or_equal:rental_start_date'],
            'replacement_plate' => ['nullable', 'string', 'max:20'],
            'mileage' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:2000'],
        ], [
            'plate_number.unique' => 'Plat nomor ini sudah terdaftar.',
            'rental_end_date.after_or_equal' => 'Tanggal akhir sewa tidak boleh sebelum tanggal mulai sewa.',
        ]);

        $data['plate_number'] = strtoupper(preg_replace('/\s+/', ' ', trim($data['plate_number'])));
        $data['ownership'] = $ownership;
        $data['is_active'] = $request->boolean('is_active');
        $data['company_name'] = !empty($data['company_id'])
            ? Company::find($data['company_id'])?->short_name ?: Company::find($data['company_id'])?->name
            : null;

        // fields of the other kind are not kept
        if ($rent) {
            $data['purchase_date'] = null;
            $data['purchase_price'] = null;
        } else {
            $data = array_merge($data, ['rental_supplier_id' => null, 'monthly_rent' => null, 'rental_start_date' => null, 'rental_end_date' => null]);
        }

        return $data;
    }

    private function formData(Vehicle $vehicle): array
    {
        $distinct = fn (string $column) => Vehicle::whereNotNull($column)->distinct()->orderBy($column)->pluck($column);

        return [
            'vehicle' => $vehicle,
            'companies' => Company::where('is_active', true)->orderByDesc('is_default')->orderBy('name')->get(['id', 'name', 'short_name']),
            'suppliers' => ErpSupplier::orderBy('name')->get(['id', 'name', 'supplier_code']),
            'subProjects' => ErpSubProject::orderBy('sub_project_code')->get(['id', 'sub_project_code', 'name']),
            // earlier answers are offered again, so the same city / brand is not typed ten ways
            'suggestions' => collect(['brand', 'model', 'vehicle_type', 'color', 'condition', 'usage_status', 'region', 'location', 'current_area'])
                ->mapWithKeys(fn ($c) => [$c => $distinct($c)]),
        ];
    }
}
