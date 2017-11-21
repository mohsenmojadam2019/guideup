<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Guide;
use Illuminate\Auth\Access\HandlesAuthorization;

class GuidePolicy extends Policy
{
    /**
     * Determine whether the user can view the guide.
     *
     * @param  \App\User  $user
     * @param  \App\Guide  $guide
     * @return mixed
     */
    public function view(User $user, Guide $guide)
    {
        //
    }

    /**
     * Determine whether the user can create guides.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        //
    }

    /**
     * Determine whether the user can update the guide.
     *
     * @param  \App\User  $user
     * @param  \App\Guide  $guide
     * @return mixed
     */
    public function update(User $user, Guide $guide)
    {
        return ($user->id == $guide->user_id);
    }

    /**
     * Determine whether the user can delete the guide.
     *
     * @param  \App\User  $user
     * @param  \App\Guide  $guide
     * @return mixed
     */
    public function delete(User $user, Guide $guide)
    {
        return ($user->id == $guide->user_id);
    }
}
