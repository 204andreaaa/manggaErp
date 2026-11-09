<?php

namespace App\Models\Erp;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/** Advance-Expense Declaration Non Project (ADVEXNPJ), attached to an employee. */
class EmployeeAdvance extends Model
{
    use SoftDeletes;

    protected $connection = 'tenant';
    protected $table = 'erp_employee_advances';

    public const STATUSES = ['Draft', 'Completed', 'Cancelled'];
    public const PAYMENT_STATUSES = ['Unpaid', 'Paid'];
    public const EXPENSE_TYPES = [
        'Pinjaman Karyawan',
        'Beasiswa / Tunjangan',
        'Klaim Asuransi / Reimburse',
        'Own Risk / Denda',
        'Acara Kantor',
        'Lainnya',
    ];

    /** Theme colour (bg-label-*, text-*) and icon per expense type. */
    public const EXPENSE_TYPE_STYLES = [
        'Pinjaman Karyawan' => ['warning', 'bx-money'],
        'Beasiswa / Tunjangan' => ['info', 'bx-book-reader'],
        'Klaim Asuransi / Reimburse' => ['success', 'bx-plus-medical'],
        'Own Risk / Denda' => ['danger', 'bx-car'],
        'Acara Kantor' => ['primary', 'bx-group'],
        'Lainnya' => ['secondary', 'bx-dots-horizontal-rounded'],
    ];

    protected $fillable = [
        'code',
        'employee_id',
        'department_id',
        'department_name',
        'status',
        'currency',
        'advance_request_date',
        'advance_amount',
        'description',
        'expense_type',
        'payment_status',
        'payment_date',
        'declaration_date',
        'declaration_amount',
        'declaration_description',
        'created_by_id',
        'updated_by_id',
    ];

    protected $casts = [
        'advance_request_date' => 'date',
        'payment_date' => 'date',
        'declaration_date' => 'date',
        'advance_amount' => 'decimal:2',
        'declaration_amount' => 'decimal:2',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }

    public function statusColor(): string
    {
        return ['Completed' => 'success', 'Cancelled' => 'danger'][$this->status] ?? 'secondary';
    }

    public function paymentColor(): string
    {
        return ['Paid' => 'success', 'Unpaid' => 'warning'][$this->payment_status] ?? 'secondary';
    }

    /** [colour, icon] for the expense type. */
    public function typeStyle(): array
    {
        return self::EXPENSE_TYPE_STYLES[$this->expense_type] ?? ['secondary', 'bx-receipt'];
    }

    /** Advance minus declaration; null until something is declared. */
    public function difference(): ?float
    {
        return $this->declaration_amount === null ? null : (float) $this->advance_amount - (float) $this->declaration_amount;
    }

    /** ADVEXNPJ-00001, ADVEXNPJ-00002, ... (deleted rows keep their number). */
    public static function nextCode(): string
    {
        $last = static::withTrashed()->where('code', 'like', 'ADVEXNPJ-%')
            ->orderByRaw('CAST(SUBSTRING(code, 10) AS UNSIGNED) DESC')->value('code');

        return 'ADVEXNPJ-'.str_pad(((int) substr((string) $last, 9)) + 1, 5, '0', STR_PAD_LEFT);
    }
}
