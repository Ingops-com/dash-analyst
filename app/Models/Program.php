<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Program extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'version',
        'codigo',
        'fecha',
        'tipo'
    ];

    protected $casts = [
        'fecha' => 'date'
    ];

    public function companies()
    {
        return $this->belongsToMany(Company::class, 'company_program_config')
                    ->withPivot('annex_id');
    }

    public function annexes()
    {
        return $this->belongsToMany(Annex::class, 'program_annexes');
    }

    public function poes()
    {
        return $this->hasMany(Poe::class);
    }
}