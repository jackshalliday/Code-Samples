<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CustomerStatus extends Model
{
    protected $table = 'customer_status';

    public function customers()
    {
        return $this->hasMany('App\Customer', 'is_closed');
    }
}
