<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
	protected $table = 'areas';
	
	public function round()
    {
    	return $this->belongsTo('App\Round');
    }

    public function timeslot()
    {
        return $this->belongsTo('App\Timeslot');
    }

    public function customer()
    {
        return $this->hasMany('App\Customers', 'area_id');
    }

    public function orders()
    {
        return $this->hasMany('App\Order', 'area_id');
    }
}
