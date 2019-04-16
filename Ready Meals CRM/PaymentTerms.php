<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PaymentTerms extends Model
{
    protected $table = 'pay_terms';

    public function customers()
    {
        return $this->hasMany('App\Customers', 'payterm_id');
    }

    public function orders()
    {
        return $this->hasMany('App\Order', 'payterm_id');
    }
}
