<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CompanyAnnexSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'annex_id',
        'program_id',
        'file_path',
        'file_name',
        'mime_type',
        'file_size',
        'status',
        'submitted_by',
        'reviewed_by'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function annex()
    {
        return $this->belongsTo(Annex::class);
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}