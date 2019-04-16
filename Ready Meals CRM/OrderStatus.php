<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrderStatus extends Model
{
    protected $table = 'order_status';

    public function orders()
    {
        return $this->hasMany('App\Order', 'status_id');
    }
}
