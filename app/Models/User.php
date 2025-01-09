<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;



class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use  HasFactory, Notifiable;
    // HasApiTokens,

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'employee_no',
        'password',
        'role',

    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_no', 'employee_no');
    }

    /**
     * Get full name from the Employee model.
     */
    public function getFullNameAttribute()
    {
        return "{$this->employee->first_name} {$this->employee->last_name}";
    }

    /**
     * Get email from the Employee model.
     */
    public function getEmailAttribute()
    {
        return $this->employee->email_address;
    }

    public function adminAccount()
    {
        return $this->role === 'admin';
    }

    public function employeeAccount()
    {
        return $this->role === 'employee';
    }

    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = bcrypt($value);
    }
}