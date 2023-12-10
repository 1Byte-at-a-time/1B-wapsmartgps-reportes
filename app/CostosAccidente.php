<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class CostosAccidente extends Model
{
     public function scopeId($query)
  {
      
      $role=Auth::user()->role_id;
      switch($role){
          case "1":
              return $query;
          break;
          case "2":
          break;
      }
      
  }
}
