<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

class Designation extends Model
{
    use HasFactory, Notifiable, HasUuid;

    protected $fillable = [
        'uuid',
        'name',
    ];
}
