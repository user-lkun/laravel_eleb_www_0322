<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MemberAddress extends Model
{
    protected $fillable = [
        'name',
        'member_id',
        'tel',
        'province',
        'city',
        'county',
        'address',
    ];
}
