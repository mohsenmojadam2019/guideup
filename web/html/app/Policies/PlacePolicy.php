<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Place;
use Illuminate\Auth\Access\HandlesAuthorization;

class PlacePolicy extends Policy
{
    /**
     * Determine whether the user can view the place.
     *
     * @param  \App\User  $user
     * @param  \App\Place  $place
     * @return mixed
     */
    public function view(User $user, Place $place)
    {
        //
    }

    /**
     * Determine whether the user can create places.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        //
    }

    /**
     * Determine whether the user can update the place.
     *
     * @param  \App\User  $user
     * @param  \App\Place  $place
     * @return mixed
     */
    public function update(User $user, Place $place)
    {
        return $user->id == $place->created_by;
    }

    /**
     * Determine whether the user can delete the place.
     *
     * @param  \App\User  $user
     * @param  \App\Place  $place
     * @return mixed
     */
    public function delete(User $user, Place $place)
    {
        return $user->id == $place->created_by;
    }
}
