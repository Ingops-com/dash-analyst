<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CompanyProgramAnnexConfig extends Model
{
    use HasFactory;

    protected $table = 'company_program_annex_configs';

    protected $fillable = [
        'company_id',
        'program_id',
        'annex_id',
        'enabled',
    ];
}
