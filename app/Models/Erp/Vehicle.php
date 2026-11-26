<?php

namespace App\Models\Erp;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

/**
 * A company vehicle, Own (bought) or Rent (rented). Its costs are the RF lines that
 * name it once their PO is Approved/Completed: rent per period, service and others.
 * A rent paid through an approved PO also extends the rental end date.
 */
class Vehicle extends Model
{
    use SoftDeletes;

    protected $connection = 'tenant';
    protected $table = 'erp_vehicles';

    public const OWNERSHIPS = ['Own', 'Rent'];
    public const KINDS = ['Mobil', 'Motor'];
    public const COST_TYPES = ['Sewa', 'Servis', 'Lainnya'];
    /** What the rent price of a rented vehicle is for. */
    public const RENT_PERIODS = ['Bulan', 'Tahun'];
    /** PO statuses whose lines count as a real cost of the vehicle. */
    public const COUNTED_PO_STATUSES = ['Approved', 'Completed'];
    /** STNK / pajak due within this many days is flagged. */
    public const EXPIRY_WARNING_DAYS = 30;

    protected $fillable = [
        'plate_number', 'ownership', 'kind', 'brand', 'model', 'vehicle_type',
        'production_year', 'color', 'condition', 'usage_status', 'region', 'location', 'current_area',
        'sub_project_id', 'pic', 'stnk_name', 'stnk_address', 'chassis_number', 'engine_number',
        'stnk_expiry_date', 'tax_expiry_date', 'purchase_date', 'purchase_price', 'rental_supplier_id',
        'rent_price', 'rent_period', 'rental_start_date', 'rental_end_date', 'replacement_plate', 'mileage', 'is_active',
        'description', 'created_by_id', 'updated_by_id',
    ];

    protected $casts = [
        'stnk_expiry_date' => 'date',
        'tax_expiry_date' => 'date',
        'purchase_date' => 'date',
        'rental_start_date' => 'date',
        'rental_end_date' => 'date',
        'purchase_price' => 'decimal:2',
        'rent_price' => 'decimal:2',
        'is_active' => 'boolean',
        'production_year' => 'integer',
        'mileage' => 'integer',
    ];

    public function subProject()
    {
        return $this->belongsTo(ErpSubProject::class, 'sub_project_id');
    }

    public function rentalSupplier()
    {
        return $this->belongsTo(ErpSupplier::class, 'rental_supplier_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by_id');
    }

    public function requestFormItems()
    {
        return $this->hasMany(RequestFormItem::class, 'vehicle_id');
    }

    public function isRent(): bool
    {
        return $this->ownership === 'Rent';
    }

    /** PO lines of this vehicle whose PO is Approved/Completed, newest first. */
    public function costLines(): Collection
    {
        return ErpPurchaseOrderItem::query()
            ->whereHas('requestFormItem', fn ($q) => $q->where('vehicle_id', $this->id))
            ->whereHas('purchaseOrder', fn ($q) => $q->whereIn('status', self::COUNTED_PO_STATUSES))
            ->with(['requestFormItem.requestForm', 'purchaseOrder.supplier'])
            ->get()
            ->sortByDesc(fn ($line) => $line->purchaseOrder->date ?? $line->created_at)
            ->values();
    }

    /**
     * The end of the rent: the date typed on the vehicle, or a later period paid by an
     * approved rent PO. Nothing to undo when such a PO is cancelled: it simply stops counting.
     */
    public function effectiveRentalEndDate(): ?Carbon
    {
        if (!$this->isRent()) {
            return null;
        }

        $paidUntil = RequestFormItem::query()
            ->where('vehicle_id', $this->id)
            ->where('vehicle_cost_type', 'Sewa')
            ->whereHas('purchaseOrderItems.purchaseOrder', fn ($q) => $q->whereIn('status', self::COUNTED_PO_STATUSES))
            ->max('rental_end_date');

        $dates = array_filter([$this->rental_end_date, $paidUntil ? Carbon::parse($paidUntil) : null]);

        return $dates ? max($dates) : null;
    }

    /** "Expired" once the rent's end date has passed, otherwise "Not Yet"; null for an own vehicle. */
    public function rentStatus(?Carbon $end = null): ?string
    {
        if (!$this->isRent()) {
            return null;
        }
        $end ??= $this->effectiveRentalEndDate();

        return $end && $end->lt(today()) ? 'Expired' : 'Not Yet';
    }

    /** expired / soon (within EXPIRY_WARNING_DAYS) / ok / null when no date. */
    public static function expiryState($date): ?string
    {
        if (!$date) {
            return null;
        }
        $date = Carbon::parse($date)->startOfDay();
        if ($date->lt(today())) {
            return 'expired';
        }

        return $date->lte(today()->addDays(self::EXPIRY_WARNING_DAYS)) ? 'soon' : 'ok';
    }

    /** Badge colour of an expiry state. */
    public static function expiryColor(?string $state): string
    {
        return match ($state) {
            'expired' => 'danger',
            'soon' => 'warning',
            'ok' => 'success',
            default => 'secondary',
        };
    }
}
