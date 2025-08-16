<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Salary extends Model
{
    protected $guarded=[];
    protected $casts = [
        'base_salary' => 'decimal:2',
        'leave_deduction' => 'decimal:2',
        'penalty_deduction' => 'decimal:2',
        'final_salary' => 'decimal:2',
        'month' => 'integer',
        'year' => 'integer',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}

