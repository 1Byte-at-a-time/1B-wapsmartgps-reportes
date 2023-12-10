<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use TCG\Voyager\Traits\VoyagerUser;
use Illuminate\Support\Facades\Auth;

class Center extends Model
{
  public function scopeId($query)
  {
      
      $role=Auth::user()->role_id;
      switch($role){
          case "1":
              return $query;
          break;
          case "2":
              return $query->where('id', Auth::user()->center_id);
          break;
      }
      
  }
}
