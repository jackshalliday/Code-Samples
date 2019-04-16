<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CustomerCategory extends Model
{
    protected $table = 'customer_categories';

    public function customers()
    {
        return $this->hasMany('App\Customers', 'category_id');
    }
}
