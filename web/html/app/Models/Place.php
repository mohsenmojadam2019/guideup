<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Place extends Model
{
    use \App\Traits\UserTrait;
    use SoftDeletes;

    protected $hidden = ['deleted_at', 'cover', 'created_by', 'created_at', 'updated_at', 'galleriesRelation', 'reviewsRelation', 'pivot', 'city', 'state', 'country'];
    protected $guarded = ['id'];
    protected $dates = ['deleted_at', 'created_at', 'updated_at'];
    protected $appends = ['cover_url', 'cover_thumbnail_url', 'total_guides', 'total_gallery', 'total_review', 'score', 'city_name', 'state_name', 'country_name'];

    public function city()
    {
        return $this->belongsTo(Place::class, 'city_id', 'id');
    }
    
    public function state()
    {
        return $this->belongsTo(Place::class, 'state_id', 'id');
    }

    public function country()
    {
        return $this->belongsTo(Place::class, 'country_id', 'id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_place');
    }

    public function guides()
    {
        return $this->belongsToMany(Guide::class, 'guide_place');
    }

    public function galleries()
    {
        return $this->hasMany(Gallery::class);
    }
    
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    public function getCoverUrlAttribute()
    {
        return ((isset($this->attributes['cover']) && $this->attributes['cover'] != "") ? asset("/assets/images/gallery/".$this->attributes['cover']) : "");
    }

    public function getCoverThumbnailUrlAttribute()
    {
        return ((isset($this->attributes['cover']) && $this->attributes['cover'] != "") ? asset("/assets/images/gallery/thumbnail/".$this->attributes['cover']) : "");
    }
	
    public function getCityNameAttribute()
    {
		$city = $this->city()->first();
        return ($city != null ? $city->name : null);
    }
	
	public function getStateNameAttribute()
    {
		$state = $this->state()->first();
        return ($state != null ? $state->name : null);
    }
	
	public function getCountryNameAttribute()
    {
		$country = $this->country()->first();
        return ($country != null ? $country->name : null);
    }	
	
    public function getLatitudeAttribute()
    {
        return (isset($this->attributes['latitude']) && $this->attributes['latitude'] != null ? $this->attributes['latitude'] : 0);
    }	
	
    public function getLongitudeAttribute()
    {
        return (isset($this->attributes['longitude']) && $this->attributes['longitude'] != null ? $this->attributes['longitude'] : 0);
    }	

    public function getTotalGuidesAttribute()
    {
        $related = $this->guides()
            ->selectRaw('place_id, count(id) as total')
            ->groupBy('place_id')
            ->get();
        return $related && isset($related[0]) ? $related[0]->total : 0;
    }

    public function getTotalGalleryAttribute()
    {
        $related = $this->galleries()
            ->selectRaw('place_id, count(id) as total')
            ->groupBy('place_id')
            ->get();

        return $related && isset($related[0]) ? $related[0]->total : 0;
    }

    public function reviewsRelation()
    {
        return $this->hasOne(Review::class)
            ->selectRaw('place_id, count(id) as total, avg(score) as score')
            ->groupBy('place_id');
    }

    public function getTotalReviewAttribute()
    {
        // if relation is not loaded already, let's do it first
        if ( ! array_key_exists('reviewsRelation', $this->relations)) 
            $this->load('reviewsRelation');
        
        $related = $this->getRelation('reviewsRelation');

        return $related ? $related->total : 0;
    }

    public function getScoreAttribute()
    {
        // if relation is not loaded already, let's do it first
        if ( ! array_key_exists('reviewsRelation', $this->relations)) 
            $this->load('reviewsRelation');
        
        $related = $this->getRelation('reviewsRelation');
        
        return $related ? number_format($related->score,1) : 0;
    }
}