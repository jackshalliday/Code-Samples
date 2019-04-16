<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PrimarySources extends Model
{
    protected $table = 'primary_sources';

    public function customers()
    {
        return $this->hasMany('App\Customers', 'primarysource_id');
    }
}
