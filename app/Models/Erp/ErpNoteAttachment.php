<?php

namespace App\Models\Erp;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class ErpNoteAttachment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'erp_notes_attachments';

    protected $fillable = [
        'type',
        'content',
        'file_path',
        'file_name',
        'user_id',
    ];

    public function notable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
