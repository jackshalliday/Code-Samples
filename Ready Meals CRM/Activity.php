<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    protected $table = 'activities';

	public function customer()
    {
    	return $this->belongsTo('App\Customers', 'customer_id');
    }
}
