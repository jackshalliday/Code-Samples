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
use App\Http\Requests\RoundFormRequest;
use App\PaymentTerms;
use App\PrimarySources;
use App\Round;
use App\Timeslot;
use App\User;
use App\OrderStatus;
use App\Order;
use App\Items;
use App\ItemsToFranchise;

class RoundController extends Controller
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
            $franchises = Franchise::pluck('franchise_name', 'id');

            $users = User::leftJoin('franchises','users.franchise_id', '=', 'franchises.id')
            ->select(['users.id AS user_id', \DB::raw('CONCAT(users.username, " (", franchises.franchise_name, ")") AS user_name')])
            ->orderBy('franchises.id', 'asc')
            ->get();

            $user_list = $users->pluck('user_name', 'user_id');
        }
        else {
            $franchises = Franchise::where('id', $user->franchise_id)->pluck('franchise_name', 'id');
            $user_list = User::where('franchise_id', $user->franchise_id)->pluck('username', 'id');
        }

    	$round = new Round;
        return View::make('round.index', compact('round', 'user', 'franchises', 'user_list'));
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
    public function store(RoundFormRequest $request)
    {
    	$user = \Auth::user();

        if($user->access_level >= 2) {
            $franchises = Franchise::pluck('franchise_name', 'id');

            $users = User::leftJoin('franchises','users.franchise_id', '=', 'franchises.id')
            ->select(['users.id AS user_id', \DB::raw('CONCAT(users.username, " (", franchises.franchise_name, ")") AS user_name')])
            ->orderBy('franchises.id', 'asc')
            ->get();

            $user_list = $users->pluck('user_name', 'user_id');
        }
        else {
            $franchises = Franchise::where('id', $user->franchise_id)->pluck('franchise_name', 'id');
            $user_list = User::where('franchise_id', $user->franchise_id)->pluck('username', 'id');
        }
    	$round = new Round;

    	if(\Input::get('update')) {

            $round = Round::find($request->round_id);
    	}

        $days = array("Monday", "Tuesday", "Wednesday", "Thursday", "Friday");

        $round->round_name = $request->round_name;
        $round->round_day = $days[$request->round_day];
        $round->franchise_id = $request->franchise_id;
        $round->notes = $request->notes;
        $round->user_id = $request->user_id;

        if(!\Input::get('update')) {

            $round->created_by = $user->id;
        }
        
        $round_exists = \DB::table('rounds')->where('round_name', $request->round_name)->first();

        if(!is_null($round_exists)) {

            if($round->id != $round_exists->id) {

                \Session::flash('error_message', 'Round name already exists! Please try again.');
                return View::make('round.index', compact('round', 'user', 'franchises', 'user_list'));
            }
        }

        $round->save();

        if(\Input::get('update')) {

            $customers = Customers::where('round_id', $round->id)->get();

            foreach($customers as $customer) {
                $customer->user_id = $round->user_id;
                $customer->save();
            }

            \Session::flash('success_message', 'Round ' . $round->round_name . ' successfully updated!');
        }
        else {

            \Session::flash('success_message', 'Round ' . $round->round_name . ' successfully created!');
        }

        $round = new Round;
	    return View::make('round.index', compact('round', 'user', 'franchises', 'user_list'));
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
    public function update($id, RoundFormRequest $request)
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
    public function ajaxRoundModal($round_id) {

        $round_id = $round_id;
        $user = \Auth::user();
        $round = \DB::table('rounds')->where('id', $round_id)->first();

        if(is_null($round)) {

            $array = array(
            'round_name' => "", 
            'round_day' => "", 
            'franchise_id' => "", 
            'created_by' => "",
            'created_at' => "",
            'updated_at' => "", 
            'notes' => "",
            'user_id' => "");

            $json = json_encode($array);
            return response()->json($json);
        }

        $created_by = User::find($round->created_by);

        $days = array("Monday", "Tuesday", "Wednesday", "Thursday", "Friday");
        $day_number = array_search($round->round_day, $days);

        $array = array(
            'round_name' => $round->round_name, 
            'round_day' => $day_number, 
            'franchise_id' => $round->franchise_id, 
            'created_by' => $created_by->username,
            'created_at' => $round->created_at,
            'updated_at' => $round->updated_at, 
            'notes' => $round->notes,
            'user_id' => $round->user_id);

        $json = json_encode($array);
        return response()->json($json);
    }

    /**
     * Return all rounds data through ajax.
     *
     * @param  int  $id
     * @return Response
     */
    public function roundData() {

        $user = \Auth::user();

        if($user->access_level >= 2) {

            $rounds = Round::leftJoin('users','rounds.user_id', '=', 'users.id')
            ->leftJoin('franchises','rounds.franchise_id', '=', 'franchises.id')
            ->select(['rounds.id AS round_id', 'round_day', 'round_name', 'rounds.created_at AS created_at', 'rounds.notes AS notes', 'users.username AS user_id', 'franchises.franchise_name AS franchise'])
            ->orderBy('franchises.franchise_name', 'asc')
            ->orderBy('rounds.round_name', 'asc')
            ->get();
        }
        else {

            $rounds = Round::leftJoin('users','rounds.user_id', '=', 'users.id')
            ->leftJoin('franchises','rounds.franchise_id', '=', 'franchises.id')
            ->select(['rounds.id AS round_id','round_day', 'round_name', 'rounds.created_at AS created_at', 'rounds.notes AS notes', 'users.username AS user_id', 'franchises.franchise_name AS franchise'])
            ->where('rounds.franchise_id', '=', $user->franchise_id)
            ->orderBy('franchises.franchise_name', 'asc')
            ->orderBy('rounds.round_name', 'asc')
            ->get();
        }

        return Datatables::of($rounds)
        ->addColumn('action', function ($round) {

            return '<div class="btn-group"><button type="button" class="open-roundsModal btn btn-default btn-xs btn-flat" data-id="' . $round->round_id .'" data-toggle="modal" data-target="#roundsModal"><i class="fa fa-arrow-right"></i></button></div>';

        })->make(true); 
    }
}
