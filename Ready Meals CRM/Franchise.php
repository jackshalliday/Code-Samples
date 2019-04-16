<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Franchise extends Model
{
	protected $table = 'franchises';

	public function rounds()
    {
        return $this->hasMany('App\Round');
    }

    public function customers()
    {
        return $this->hasMany('App\Customers');
    }

    public function orders()
    {
    	return $this->hasMany('App\Order', 'franchise_id');
    }

    public function itemsToFranchises()
    {
    	return $this->hasMany('App\ItemsToFranchises', 'franchise_id');
    }

    public function users()
    {
        return $this->hasMany('App\User', 'franchise_id');
    }

    public function created_by()
    {
        return $this->belongsTo('App\User', 'created_by');
    }  
}
