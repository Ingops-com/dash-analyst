<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Poe extends Model
{
    use HasFactory;

    protected $fillable = [
        'program_id',
        'nombre',
        'descripcion',
        'frecuencia',
        'codigo_poe'
    ];

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function records()
    {
        return $this->hasMany(CompanyPoeRecord::class);
    }
}