<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CompanyPoeRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'poe_id',
        'observaciones',
        'fecha_ejecucion',
        'ejecutado_por'
    ];

    protected $casts = [
        'fecha_ejecucion' => 'date'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function poe()
    {
        return $this->belongsTo(Poe::class);
    }

    public function executor()
    {
        return $this->belongsTo(User::class, 'ejecutado_por');
    }
}