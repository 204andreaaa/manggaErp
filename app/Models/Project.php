<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Project extends Model
{
    use HasFactory;

    protected $connection = 'master';

    protected $fillable = [
        'name',
        'db_name',
        'db_host',
        'db_port',
        'db_username',
        'db_password',
        'is_active',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'project_user', 'project_id', 'user_id');
    }
}
