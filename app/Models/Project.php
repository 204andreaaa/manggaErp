<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

/**
 * A project (tenant) is one PT with its own database. Its identity (legal name, logo,
 * address, NPWP) is the letterhead of the documents printed inside it.
 */
class Project extends Model
{
    use HasFactory;

    protected $connection = 'master';

    /** The PT identity shown on printouts. */
    public const IDENTITY_FIELDS = ['legal_name', 'address', 'phone', 'fax', 'email', 'website', 'tax_number'];

    protected $fillable = [
        'name',
        'db_name',
        'db_host',
        'db_port',
        'db_username',
        'db_password',
        'is_active',
        'legal_name',
        'address',
        'phone',
        'fax',
        'email',
        'website',
        'tax_number',
        'logo_path',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'project_user', 'project_id', 'user_id');
    }

    /** The project the user is working in (falls back to the first active one). */
    public static function current(): ?self
    {
        $id = session('project_id');

        return ($id ? static::find($id) : null) ?? static::where('is_active', true)->orderBy('id')->first();
    }

    /** The PT name for letterheads, e.g. "PT Mandiri Daya Utama Nusantara". */
    public function letterheadName(): string
    {
        return $this->legal_name ?: $this->name;
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path && file_exists(public_path($this->logo_path)) ? asset($this->logo_path) : null;
    }
}
