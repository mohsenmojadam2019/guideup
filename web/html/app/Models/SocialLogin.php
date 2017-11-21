<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocialLogin extends Model
{
    protected $hidden = ['created_at', 'updated_at'];
    protected $guarded = ['id'];
    protected $dates = ['created_at', 'updated_at'];
    
    public function user()
    {
	    return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
