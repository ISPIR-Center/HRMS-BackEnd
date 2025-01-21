<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeClassification extends Model
{
    use HasFactory;

    protected $fillable = [
        'classification',
    ];

    public function employee()
    {
        return $this->hasMany(Employee::class, 'classification_id');
    }
}
