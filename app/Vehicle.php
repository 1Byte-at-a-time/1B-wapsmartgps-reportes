<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use TCG\Voyager\Traits\VoyagerUser;
use Illuminate\Support\Facades\Auth;

class Vehicle extends Model
{
    public function scopeId($query)
    {
        $role=Auth::user()->role_id;
        switch($role){
            case "1":
                return $query;
            break;
            case "2":
                return $query->where('center_id', Auth::user()->center_id);
            break;
            case "3":
               return $query->whereIn('id',function ($query) {
                    $query->select('vehicle_id')
                ->distinct()->from('user_v_c')
                ->Where('user_id',Auth::user()->id);
                });
            break;
        }
        
    }
}
