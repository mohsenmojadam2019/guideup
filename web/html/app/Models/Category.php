<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    public function places()
    {
        return $this->belongsToMany(Place::class, 'place_category');
    }
}
