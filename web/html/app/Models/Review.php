<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use stdClass;

class Review extends Model
{
    use SoftDeletes;

    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];
    protected $guarded = ['id'];
    protected $dates = ['created_at', 'updated_at', 'deleted_at','deleted_at'];
    protected $appends = ['date', 'user', 'guide', 'place'];
    
    public function user()
    {
	    return $this->belongsTo(User::class);
    }

    public function guide()
    {
        return $this->belongsTo(Guide::class);
    }

    public function place()
    {
        return $this->belongsTo(Place::class);
    }
    
    public function getDateAttribute()
    {
        return isset($this->attributes['created_at']) ? $this->attributes['created_at'] : "";
    }

    public function getUserAttribute()
    {
        $obj = null;
        $user = $this->user()->first(); 
        if($user != null)
        {
            $obj = new stdClass();

            $obj->id = $user['id'];
            $obj->name = $user['name'];
            $obj->avatar_url = $user['avatar_url']; 
        }
        return $obj;
    }

    public function getGuideAttribute()
    {
        $obj = null;
        $guide = $this->guide()->first(); 
        if($guide != null)
        {
            $obj = new stdClass();

            $obj->id = $guide['id'];
            $obj->user = ['name' => $guide->user->name, 'email' => $guide->user->email ];
            $obj->avatar_url = $guide['avatar_url']; 
            $obj->company = $guide['company'];
        }
        return $obj;
    }

    public function getPlaceAttribute()
    {
        $obj = null;
        $place = $this->place()->first(); 
        if($place != null)
        {
            $obj = new stdClass();

            $obj->id = $place->id;
            $obj->name = $place->name;
            $obj->cover_thumbnail_url = $place->cover_thumbnail_url; 
        }
        return $obj;
    }
}
