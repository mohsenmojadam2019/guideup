<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    protected $hidden = ['created_at', 'updated_at', 'id', 'user_id', 'guide_id'];
   protected $guarded = ['id'];
    //
    public function user()
    {
	    return $this->hasOne(User::class);
    }

    public function guide()
    {
	    return $this->hasOne(Guide::class);
    }
}
