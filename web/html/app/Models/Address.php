<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Address extends Model
{
    use SoftDeletes;

    protected $hidden = ['created_at', 'updated_at', 'deleted_at', 'city'];
    protected $guarded = ['id'];
	protected $appends = ['city_name', 'state_name', 'country_name'];

    public function user()
    {        
        return $this->hasOne(User::class);
    }

    public function guide()
    {        
        return $this->hasOne(Guide::class);
    }

    public function city()
    {        
        return $this->belongsTo(Place::class, 'city_id');
    }
	
	    public function getCityNameAttribute()
    {
		$city = $this->city()->first();
        return ($city != null ? $city->name : null);
    }
	
	public function getStateNameAttribute()
    {
		$city = $this->city()->first();
        return ($city != null ? $city->state_name : null);
    }
	
	public function getCountryNameAttribute()
    {
		$city = $this->city()->first();
        return ($city != null ? $city->country_name : null);
    }
}
