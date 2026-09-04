<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomReport extends Model
{
    use HasFactory;

    protected $connection = 'master';
    protected $table = 'custom_reports';

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'report_type',
        'selected_columns',
        'filters',
        'date_field',
        'date_range_preset',
        'date_from',
        'date_to',
        'is_public',
    ];

    protected $casts = [
        'selected_columns' => 'array',
        'filters'          => 'array',
        'date_from'        => 'date',
        'date_to'          => 'date',
        'is_public'        => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
