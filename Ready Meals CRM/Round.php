<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Round extends Model
{
	protected $table = 'rounds';
	
	public function franchise()
    {
    	return $this->belongsTo('App\Franchise');
    }

    public function postcodes()
    {
        return $this->hasMany('App\Area');
    }

    public function customers()
    {
        return $this->hasMany('App\Customers', 'round_id');
    }

    public function orders()
    {
        return $this->hasMany('App\Order', 'round_id');
    }

    public function created_by()
    {
        return $this->belongsTo('App\User', 'created_by');
    }

    public function user()
    {
        return $this->belongsTo('App\User', 'user_id');
    }
}
