<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gallery extends Model
{
    use \App\Traits\StorageTrait;

    protected $hidden = ['image', 'created_at', 'updated_at'];
    protected $guarded = ['id'];
    protected $dates = ['created_at', 'updated_at'];
    protected $appends = ['image_url','thumbnail_url'];

    public function getImageUrlAttribute()
    {
        return (($this->attributes['image'] != null && $this->attributes['image'] != "") ? $this->getGalleryUrl($this->attributes['image']) : "");
    }

    public function getThumbnailUrlAttribute()
    {
        return (($this->attributes['image'] != null && $this->attributes['image'] != "") ? $this->getGalleryUrl($this->attributes['image']) : "");
    }

    //
    public function author()
    {
	    return $this->belongsTo(User::class);
    }

    public function place()
    {
	    return $this->belongsTo(Place::class);
    }

    public function guide()
    {
	    return $this->belongsTo(Guide::class);
    }
}
