<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserShow extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'tmdb_show_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
