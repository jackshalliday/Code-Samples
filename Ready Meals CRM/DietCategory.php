<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DietCategory extends Model
{
    protected $table = 'diet_categories';

    public function itemsToDietCategories()
    {
    	return $this->hasMany('App\ItemsToDietCategories', 'diet_category_id');
    }
}
