<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Office extends Model
{
    use HasFactory;

    protected $fillable = [
        'office_name',
    ];

    public function employees()
    {
        return $this->hasMany(Employee::class, 'office_id');
    }
}
