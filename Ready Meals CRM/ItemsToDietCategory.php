<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ItemsToDietCategory extends Model
{
    protected $table = 'items_to_diet_categories';

    public function item()
    {
    	return $this->belongsTo('App\Item', 'item_id');
    }

    public function dietCategory()
    {
    	return $this->belongsTo('App\DietCategory', 'diet_category_id');
    }
}
