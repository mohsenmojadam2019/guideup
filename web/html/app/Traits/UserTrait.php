<?php

namespace App\Traits;

use Auth;

trait UserTrait {
    public function getLoggedUser() {
        if(!Auth::guard('api')->check()) {
            return false;
        }
        return Auth::guard('api')->user();
    }
}
