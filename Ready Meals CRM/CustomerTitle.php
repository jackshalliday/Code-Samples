<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CustomerTitle extends Model
{
    protected $table = 'customer_titles';

    public function customers()
    {
        return $this->hasMany('App\Customers', 'title_id');
    }
}
