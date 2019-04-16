<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use View;
use Postcode;
use Yajra\Datatables\Facades\Datatables;

use App\Activity;
use App\Area;
use App\Customers;
use App\CustomerTitle;
use App\CustomerCategory;
use App\Franchise;
use App\Http\Requests\UserFormRequest;
use App\PaymentTerms;
use App\PrimarySources;
use App\Round;
use App\Timeslot;
use App\User;
use App\OrderStatus;
use App\Order;
use App\Items;
use App\ItemsToFranchise;

class UserController extends Controller
{
    /**
     * Instantiate a new ItemController instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('user_active');
        $this->middleware('check_access_level');  
    }

     /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
    	$user = \Auth::user();

        if($user->access_level >= 2) {
            $access_levels = \DB::table('user_access_levels')->pluck('name', 'id');
            $franchises = Franchise::pluck('franchise_name', 'id');
        }
        else {
            $access_levels = \DB::table('user_access_levels')->where('id', '<=', $user->access_level)->pluck('name', 'id');
            $franchises = Franchise::where('id', $user->franchise_id)->pluck('franchise_name', 'id');
        }
         
        $new_user = new User;
        return View::make('user.index', compact('new_user', 'user', 'access_levels', 'franchises'));  
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        $user = \Auth::user();
        return View::make('errors.404', compact('user'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(UserFormRequest $request)
    {
    	$user = \Auth::user();
    	$new_user = new User;
        
        if($user->access_level >= 2) {
            $access_levels = \DB::table('user_access_levels')->pluck('name', 'id');
            $franchises = Franchise::pluck('franchise_name', 'id');
        }
        else {
            $access_levels = \DB::table('user_access_levels')->where('id', '<=', $user->access_level)->pluck('name', 'id');
            $franchises = Franchise::where('id', $user->franchise_id)->pluck('franchise_name', 'id');
        }

        if(\Input::get('reset')) {

            $new_user = User::find($request->user_id);
            $password = $this->random_password();
            
            $new_user->password = bcrypt($password);
            $new_user->save();

            \Session::flash('success_message', 'Password successfully reset!');
            \Session::flash('warning_message', 'User password is: ' . $password);
            return View::make('user.index', compact('new_user', 'user', 'access_levels', 'franchises'));  
        }

    	if(\Input::get('update')) {

    		$new_user = User::find($request->user_id);
            $new_user->is_closed = $request->is_closed;
    	}
        else {
            $new_user->username = strtolower($request->name[0] . '.' . $request->user_surname);
        }

        $new_user->name = $request->name;
        $new_user->user_surname = $request->user_surname;
        $new_user->email = $request->email;
        $new_user->notes = $request->notes;
        $new_user->franchise_id = $request->franchise_id;
        $new_user->access_level = $request->access_level;
        
        $username_test = $new_user->username;
        $exists = true;
        $i = 0;

        while($exists) {

            $username_exists = \DB::table('users')->where('username', $username_test)->first();

            if(!is_null($username_exists)) {

                if($new_user->id != $username_exists->id) {

                    $username_test = $new_user->username . ++$i;
                }
                else {
                    $exists = false;
                }
            }
            else {
                $exists = false;
                $new_user->username = $username_test;
            }
        }

        if(!\Input::get('update')) {

            $new_user->is_closed = 0;
            $password = $this->random_password();
            $new_user->password = bcrypt($password);

            \Session::flash('success_message', 'User ' . $new_user->username . ' successfully created!');
            \Session::flash('warning_message', 'User password is: ' . $password);
        }
        else {

            \Session::flash('success_message', 'User ' . $new_user->username . ' successfully updated!');
        }

        $new_user->save();

        $new_user = new User;
        return View::make('user.index', compact('new_user', 'user', 'access_levels', 'franchises'));  
    }

    /* code from: https://hugh.blog/2012/04/23/simple-way-to-generate-a-random-password-in-php/ */
    private function random_password( $length = 10 ) {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_-=+;:,.?";
        $password = substr( str_shuffle( $chars ), 0, $length );
        return $password;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id)
    {
        $user = \Auth::user();
        return View::make('errors.404', compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
        $user = \Auth::user();
        return View::make('errors.404', compact('user'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function update($id, UserFormRequest $request)
    {
        $user = \Auth::user();
        return View::make('errors.404', compact('user'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        $user = \Auth::user();
        return View::make('errors.404', compact('user'));
    }

    /**
     * Return the specified resource through ajax.
     *
     * @param  int  $id
     * @return Response
     */
    public function ajaxUserModal($user_id) {

        $user_id = $user_id;
        $user = \Auth::user();
        $new_user = \DB::table('users')->where('id', $user_id)->first();

        if(is_null($new_user)) {

            $array = array(
            'name' => "", 
            'user_surname' => "", 
            'email' => "", 
            'username' => "", 
            'franchise_id' => "", 
            'access_level' => "", 
            'is_closed' => "",
            'notes'=> "");

            $json = json_encode($array);
            return response()->json($json);
        }
        else {

            $array = array(
            'name' => $new_user->name, 
            'user_surname' => $new_user->user_surname, 
            'email' => $new_user->email, 
            'username' => $new_user->username, 
            'franchise_id' => $new_user->franchise_id, 
            'access_level' => $new_user->access_level, 
            'is_closed' => $new_user->is_closed,
            'notes'=> $new_user->notes);

            $json = json_encode($array);
            return response()->json($json);
        } 
    }

    /**
     * Return all user data through ajax.
     *
     * @param  int  $id
     * @return Response
     */
    public function usersData() {

        $user = \Auth::user();

        if($user->access_level >= 2) {

            $new_users = User::leftJoin('user_access_levels','users.access_level', '=', 'user_access_levels.id')
            ->leftJoin('user_status' , 'users.is_closed', '=', 'user_status.id')
            ->leftJoin('franchises' , 'users.franchise_id', '=', 'franchises.id')
            ->select(['users.id AS user_id', 'users.username AS username', 'users.name AS name', 'users.user_surname AS surname', 'users.email AS email', 'user_access_levels.name AS access_level', 'franchises.franchise_name AS franchise', 'user_status.name AS status'])
            ->orderBy('users.is_closed', 'asc')
            ->orderBy('franchises.franchise_name', 'asc')
            ->orderBy('users.username', 'asc')
            ->get();
        }
        else {

            $new_users = User::leftJoin('user_access_levels','users.access_level', '=', 'user_access_levels.id')
            ->leftJoin('user_status' , 'users.is_closed', '=', 'user_status.id')
            ->leftJoin('franchises' , 'users.franchise_id', '=', 'franchises.id')
            ->select(['users.id AS user_id', 'users.username AS username', 'users.name AS name', 'users.user_surname AS surname', 'users.email AS email', 'user_access_levels.name AS access_level', 'franchises.franchise_name AS franchise', 'user_status.name AS status'])
            ->where('users.franchise_id', '=', $user->franchise_id)
            ->orderBy('users.is_closed', 'asc')
            ->orderBy('franchises.franchise_name', 'asc')
            ->orderBy('users.username', 'asc')
            ->get();
        }

        return Datatables::of($new_users)
        ->addColumn('action', function ($new_user) {

           return '<div class="btn-group"><button type="button" class="open-usersModal btn btn-default btn-xs btn-flat" data-id="' . $new_user->user_id .'" data-toggle="modal" data-target="#usersModal"><i class="fa fa-arrow-right"></i></button></div>';
        })->make(true); 
    }
}
