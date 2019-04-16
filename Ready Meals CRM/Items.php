<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Items extends Model
{
    protected $table = 'items';

    public function itemsToOrders()
    {
    	return $this->hasMany('App\ItemsToOrder', 'item_id');
    }

    public function itemsToFranchises()
    {
    	return $this->hasMany('App\ItemsToFranchise', 'item_id');
    }

    public function itemsToDietCategories()
    {
    	return $this->hasMany('App\ItemsToDietCategories', 'item_id');
    }
}
