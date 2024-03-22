<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    use HasFactory;
    protected $fillable = ['uid', 'before', 'after'];
    protected $table = 'images';
    //protected $keyType = 'string';

    public function user()
    {
        return $this->belongsTo(User::class, 'uid', 'uid');
    }
}
