<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ItemsToOrder extends Model
{
    protected $table = 'items_to_orders';

    public function order()
    {
    	return $this->belongsTo('App\Order', 'order_id');
    }

    public function item()
    {
    	return $this->belongsTo('App\Items', 'item_id');
    }

    public function substitute()
    {
    	return $this->belongsTo('App\Items', 'item_id');
    }
}
