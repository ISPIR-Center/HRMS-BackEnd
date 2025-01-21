<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmploymentType extends Model
{
    use HasFactory;

    protected $fillable = [
        'employment_type',
    ];

    public function employee()
    {
        return $this->hasMany(Employee::class, 'employment_type_id');
    }
}
