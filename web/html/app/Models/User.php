<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

use Laravel\Passport\HasApiTokens;
use App\Notifications\CustomResetPassword;

use Carbon;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, SoftDeletes;

    protected $token = "token";
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'born', 'gender', 'phone', 'avatar', 'fcm_token', 'is_admin'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'fcm_token', 'remember_token', 'deleted_at', 'updated_at', 'created_at', 'avatar', 'guide', 'is_admin'
    ];

    protected $appends = ['avatar_url', 'avatar_thumbnail_url', 'guide_id'];


    /*public function setPasswordAttribute($value) {
    *    $this->attributes['password'] = bcrypt($value);
    *}
*/
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

    public function getGuideIdAttribute()
    {
        return ($this->guide != null && isset($this->guide['id'])) ? $this->guide['id'] : 0;
    }

    public function getLanguagesAttribute()
    {
        return $this->Languages()->get();
    }

    /*
    * Relations 
    */

    public function address()
    {
        return $this->belongsTo(Address::class);
    }
    
    public function languages()
    {
        return $this->hasMany(Language::class);
    }
    
    public function guide()
    {
        return $this->hasOne(Guide::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function places()
    {
        return $this->belongsToMany(Place::class, 'user_place');
    }

    public function socialLogins()
    {
        return $this->hasMany(SocialLogin::class);
    }

/**
 * Send the password reset notification.
 *
 * @param  string  $token
 * @return void
 */
public function sendPasswordResetNotification($token)
{
    $this->notify(new CustomResetPassword($token, $this->name));
}
}
