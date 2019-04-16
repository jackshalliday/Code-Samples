<?php

namespace App\Http\Middleware;

use Closure;

class UserActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        //check if the user's account is deactivated
        if (\Auth::user()->is_closed) {

            \Session::flash('error_message', 'Account deactivated. Please contact tecnical support.');
            \Auth::logout();
            return \Redirect::route('login');
        } 

        return $next($request);
    }
}
