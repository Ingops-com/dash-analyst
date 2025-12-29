<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reminder extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'reminder_date',
        'notified',
        'status',
    ];

    protected $casts = [
        'notified' => 'boolean',
    ];

    protected $dates = [
        'reminder_date',
    ];

    /**
     * Obtener el usuario propietario del recordatorio
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

