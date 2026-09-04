<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    /** Tabel employees berada di database master */
    protected $connection = 'master';
    protected $table = 'employees';

    protected $fillable = [
        'user_id',
        'nik',
        'name',
        'email',
        'phone',
        'gender',
        'place_of_birth',
        'date_of_birth',
        'id_card_number',
        'tax_id',
        'bpjs_tk',
        'bpjs_kes',
        'bank_name',
        'bank_account_number',
        'bank_account_holder',
        'address',
        'emergency_contact_name',
        'emergency_contact_phone',
        'department',
        'position',
        'employment_status',
        'join_date',
        'end_contract_date',
        'basic_salary',
        'photo_path',
        'signature_path',
        'status',
        'notes',
    ];

    protected $casts = [
        'date_of_birth'     => 'date',
        'join_date'         => 'date',
        'end_contract_date' => 'date',
        'basic_salary'      => 'decimal:2',
    ];

    /* ================== RELASI ================== */

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
