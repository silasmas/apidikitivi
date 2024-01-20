<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @author Xanders
 * @see https://www.linkedin.com/in/xanders-samoth-b2770737/
 */
class PasswordReset extends Model
{
    use HasFactory;

    protected $fillable = ['email', 'phone', 'token', 'former_password', 'updated_at'];
}
