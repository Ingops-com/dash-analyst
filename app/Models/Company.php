<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Company extends Model
{
    use HasFactory;

    protected $table = 'companies';

    protected $fillable = [
        'nombre',
        'nit_empresa',
        'correo',
        'direccion',
        'telefono',
        'representante_legal',
        'encargado_sgc',
        'version',
        'fecha_inicio',
        'fecha_verificacion',
        'actividades',
        'logo_izquierdo',
        'logo_derecho',
        'logo_pie_de_pagina',
        'habilitado',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_verificacion' => 'date',
        'habilitado' => 'boolean',
    ];

    public function programs()
    {
        return $this->belongsToMany(Program::class, 'company_program_config')
                    ->withPivot('annex_id');
    }
}
