<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Employee extends Model
{
    use HasFactory;

    protected $primaryKey = 'employee_no';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'employee_no',
        'employment_type_id',
        'classification_id',
        'office_id',
        'suffix',
        'first_name',
        'middle_name',
        'last_name',
        'email_address',
        'mobile_no',
        'birthdate',
        'gender',
        'google_scholar_link',
    ];

    public function employmentType()
    {
        return $this->belongsTo(EmploymentType::class, 'employment_type_id');
    }

    public function classification()
    {
        return $this->belongsTo(EmployeeClassification::class, 'classification_id');
    }

    public function office()
    {
        return $this->belongsTo(Office::class, 'office_id');
    }

    public function ipcrs()
    {
        return $this->hasMany(Ipcr::class, 'employee_no', 'employee_no');
    }



    public function setFirstNameAttribute($value)
    {
        $this->attributes['first_name'] = ucfirst($value);
    }
}
