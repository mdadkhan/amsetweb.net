<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactSubmission extends Model
{
    protected $fillable = [
        'inquiry_type', 'name', 'email', 'phone', 'organization', 'message',
    ];
}
