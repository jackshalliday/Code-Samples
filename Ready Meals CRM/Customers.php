<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Customers extends Model
{
	protected $table = 'customers';

    // protect from mass assignment vulnerabilities
    protected $fillable = ['title_id'];
	
	public function customerCategory()
    {
    	return $this->belongsTo('App\CustomerCategory', 'category_id'); //->select('name');;
    }

    public function customerTitle()
    {
        return $this->belongsTo('App\CustomerTitle', 'title_id');
    }

    public function user()
    {
        return $this->belongsTo('App\User', 'user_id');
    }

    public function createdBy()
    {
        return $this->belongsTo('App\User', 'created_by');
    }

    public function franchise()
    {
        return $this->belongsTo('App\Franchise', 'franchise_id');
    }

    public function area()
    {
        return $this->belongsTo('App\Area', 'area_id');
    }

    public function round()
    {
        return $this->belongsTo('App\Round', 'round_id');
    }

    public function timeslot()
    {
        return $this->belongsTo('App\Timeslot', 'timeslot_id');
    }

    public function paymentTerms()
    {
        return $this->belongsTo('App\PaymentTerms', 'payterm_id');
    }

    public function primarySources()
    {
        return $this->belongsTo('App\PrimarySources', 'primarysource_id');
    }

    public function orders()
    {
        return $this->hasMany('App\Order', 'customer_id');
    }

    public function activity()
    {
        return $this->hasOne('App\Activity', 'customer_id');
    }

    public function customerStatus()
    {
        return $this->belongsTo('App\CustomerStatus', 'is_closed');
    }
}
