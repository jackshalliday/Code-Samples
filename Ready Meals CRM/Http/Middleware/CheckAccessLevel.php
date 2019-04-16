<?php

namespace App\Http\Middleware;

use Closure;

class CheckAccessLevel
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
        // check if user is at least manager level
        if (\Auth::user()->access_level < 1) {

            \Session::flash('error_message', 'Error! Access permission denied.');
            return \Redirect::route('dashboard.index');
        } 

        return $next($request);
    }
}
