<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ItemsToFranchise extends Model
{
    protected $table = 'items_to_franchises';

    public function item()
    {
    	return $this->belongsTo('App\Item', 'item_id');
    }

    public function franchise()
    {
    	return $this->belongsTo('App\Franchise', 'franchise_id');
    }
}
