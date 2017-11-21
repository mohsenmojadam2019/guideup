<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Guide extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'email', 'phone', 'cover', 'company', 'number_consil', 'avatar', 'address', 'busy', 'description', 'latitude', 'longitude', 'user_id', 'city_id', 'postal_code', 'languages'
    ];

    protected $hidden = [
       'deleted_at', 'created_at', 'updated_at', 'reviewsRelation', 'avatar'
    ];

    protected $appends = [
        'total_review', 'score', 'avatar_url', 'avatar_thumbnail_url'
    ];

    /*
    * Relations 
    */

    public function user()
    {
	    return $this->belongsTo(User::class);
    }

    public function address()
    {
        return $this->belongsTo(Address::class);
    }
    
    public function languages()
    {
        return $this->hasMany(Language::class);
    }
        
    public function galleries()
    {
	    return $this->hasMany(Gallery::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function places()
    {
        return $this->belongsToMany(Place::class, 'guide_place');
    }

    public function getAvatarUrlAttribute()
    {
        if(isset($this->attributes['avatar']) && $this->attributes['avatar'] != "")
        {
            return asset("/assets/images/avatar/".$this->attributes['avatar']);
        }
        return "";
    }

    public function getAvatarThumbnailUrlAttribute()
    {
        if(isset($this->attributes['avatar']) && $this->attributes['avatar'] != "")
        {
            return asset("/assets/images/avatar/thumbnail/".$this->attributes['avatar']);
        }
        return "";
    }

    public function reviewsRelation()
    {
        return $this->hasOne(Review::class)
            ->selectRaw('guide_id, count(id) as total, avg(score) as score')
            ->groupBy('guide_id');
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
