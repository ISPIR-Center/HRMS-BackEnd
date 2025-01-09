<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class IpcrPeriod extends Model
{
    use HasFactory;

    // protected $fillable = [
    //     'start_month_year',
    //     'end_month_year',
    //     'ipcr_period_type',
    //     'ipcr_type',
    //     'active_flag',

    // ];

    // public function ipcrs()
    // {
    //     return $this->hasMany(Ipcr::class, 'ipcr_period_id');
    // }

    // public function getIsActiveAttribute()
    // {
    //     $today = now()->toDateString();
    //     return $this->start_month_year <= $today && $this->end_month_year >= $today;
    // }
   
    protected $fillable = [
        'start_month_year',
        'end_month_year',
        'ipcr_period_type',
        'ipcr_type',
        'active_flag',
    ];

    protected $casts = [
        'active_flag' => 'boolean',
        'start_month_year' => 'date',
        'end_month_year' => 'date',
    ];

    // Auto-set active_flag when creating/updating records
    public static function boot()
    {
        parent::boot();

        static::saving(function ($period) {
            $today = Carbon::today();
            $period->active_flag = $today->between($period->start_month_year, $period->end_month_year);
        });
    }

    public function ipcrs()
    {
        return $this->hasMany(Ipcr::class, 'ipcr_period_id');
    }
}
