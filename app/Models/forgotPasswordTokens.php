<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class forgotPasswordTokens extends Model
{
    protected $fillable=['user_id','link','status'];
    use HasFactory;
}
