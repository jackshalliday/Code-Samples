<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $table = 'users';

    public function customers()
    {
        return $this->hasMany('App\Customers', 'user_id');
    }

    public function orders()
    {
        return $this->hasMany('App\Order', 'user_id');
    }

    public function created_franchises()
    {
        return $this->hasMany('App\Franchise', 'created_by');
    }

    public function created_rounds()
    {
        return $this->hasMany('App\Round', 'created_by');
    }

    public function rounds()
    {
        return $this->hasMany('App\Round', 'user_id');
    }

    public function franchise()
    {
        return $this->belongsTo('App\Franchise', 'franchise_id');
    }
}
