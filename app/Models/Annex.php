<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Annex extends Model
{
    use HasFactory;

    protected $table = 'annexes';

    protected $fillable = [
        'nombre',
        'codigo_anexo',
        'placeholder',
        'content_type',
        'tipo',
        'status',
        'table_columns',
        'table_header_color'
    ];

    protected $casts = [
        'table_columns' => 'array'
    ];

    public function programs()
    {
        return $this->belongsToMany(Program::class, 'program_annexes');
    }

    public function submissions()
    {
        return $this->hasMany(CompanyAnnexSubmission::class);
    }
}