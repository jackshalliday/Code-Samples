<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'orders';

    //protected $dates = ['delivery_date'];

    public function orderStatus()
    {
    	return $this->belongsTo('App\OrderStatus', 'status_id');
    }

    public function user()
    {
    	return $this->belongsTo('App\User', 'user_id');
    }

    public function customer()
    {
    	return $this->belongsTo('App\Customers', 'customer_id');
    }

    public function franchise()
    {
    	return $this->belongsTo('App\Franchise', 'franchise_id');
    }

    public function area()
    {
    	return $this->belongsTo('App\Area', 'area_id');
    }

    public function round()
    {
    	return $this->belongsTo('App\Round', 'round_id');
    }

    public function timeslot()
    {
    	return $this->belongsTo('App\Timeslot', 'timeslot_id');
    }

    public function payterm()
    {
    	return $this->belongsTo('App\PaymentTerms', 'payterm_id');
    }

    public function itemsToOrders()
    {
    	return $this->hasMany('App\ItemsToOrder', 'order_id');
    }
}
