<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Timeslot extends Model
{
	protected $table = 'timeslots';
    
	public function postcodes()
    {
    	return $this->hasMany('App\Area');
    }

    public function customers()
    {
    	return $this->hasMany('App\Customers', 'timeslot_id');
    }

    public function orders()
    {
    	return $this->hasMany('App\Order', 'timeslot_id');
    }
}
