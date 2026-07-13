<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $fillable = [
        'full_name',
        'gender',
        'date_of_birth',
        'phone',
        'address',
    ];
}
