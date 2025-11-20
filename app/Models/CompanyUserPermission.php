<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyUserPermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'user_id',
        'can_view_annexes',
        'can_upload_annexes',
        'can_delete_annexes',
        'can_generate_documents',
    ];
}