<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ipcr extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_no',
        'numerical_rating',
        'adjectival_rating',
        'ipcr_period_id',
        'submitted_by', 
        'validated_by',
        'submitted_date',
        'validated_date',  
        'file_path', 
        'status'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_no', 'employee_no');
    }

    public function period()
    {
        return $this->belongsTo(IpcrPeriod::class, 'ipcr_period_id');
    }

    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function validatedBy()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

}
