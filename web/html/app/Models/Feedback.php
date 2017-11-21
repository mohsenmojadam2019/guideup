<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    protected $table = 'feedbacks';

    protected $hidden = ['created_at', 'updated_at'];
    protected $guarded = ['id'];
    protected $dates = ['created_at', 'updated_at'];
    protected $appends = ['date'];
    
    public function getDateAttribute()
    {
        return $this->attributes['created_at'];
    }
}
