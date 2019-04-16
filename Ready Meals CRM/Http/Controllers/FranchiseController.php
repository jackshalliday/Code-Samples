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
use App\Http\Requests\FranchiseFormRequest;
use App\PaymentTerms;
use App\PrimarySources;
use App\Round;
use App\Timeslot;
use App\User;
use App\OrderStatus;
use App\Order;
use App\Items;
use App\ItemsToFranchise;

class FranchiseController extends Controller
{
    /**
     * Instantiate a new FranchiseController instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('user_active');
        //$this->middleware('check_access_level', ['except' => ['index']]); 
    }

     /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
    	$user = \Auth::user();

        if($user->access_level < 2) {
            \Session::flash('error_message', 'Error! Access permission denied.');
            return \Redirect::route('dashboard.index');
        }

    	$franchise = new Franchise;
        return View::make('franchise.index', compact('franchise', 'user'));
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
    public function store(FranchiseFormRequest $request)
    {
    	$user = \Auth::user();

        if($user->access_level < 2) {
            \Session::flash('error_message', 'Error! Access permission denied.');
            return \Redirect::route('dashboard.index');
        }
        
    	$franchise = new Franchise;

    	if(\Input::get('update')) {

    		$franchise = Franchise::find($request->id);
    	}

        $franchise->franchise_name = $request->franchise_name;
        $franchise->notes = $request->notes;
        $franchise->created_by = $user->id;

        $franchise_exists = \DB::table('franchises')->where('franchise_name', $request->franchise_name)->first();

        if(!is_null($franchise_exists)) {

            if($franchise->id != $franchise_exists->id) {

                \Session::flash('error_message', 'Franchise name already exists! Please try again.');
                return View::make('franchise.index', compact('franchise', 'user'));
            }
        }

        $franchise->save();

        if(!\Input::get('update')) {

            $items = \DB::table('items')->get();

            foreach($items AS $item) {

                $items_to_franchise = new ItemsToFranchise;

                $items_to_franchise->item_id = $item->id;
                $items_to_franchise->franchise_id = $franchise->id;
                $items_to_franchise->pop_up = "";
                $items_to_franchise->stock = 0;
                $items_to_franchise->committed = 0;

                $items_to_franchise->save();
            }
        }

        if(\Input::get('update')) {

            \Session::flash('success_message', 'Franchise ' . $franchise->franchise_name . ' successfully updated!');
        }
        else {

            \Session::flash('success_message', 'Franchise ' . $franchise->franchise_name . ' successfully created!');
        }

	    $franchise = new Franchise;
	    return View::make('franchise.index', compact('franchise', 'user'));
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
    public function update($id, FranchiseFormRequest $request)
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
    public function ajaxFranchiseModal($franchise_id) {

        $franchise_id = $franchise_id;
        $user = \Auth::user();
        $franchise = Franchise::find($franchise_id);

        if(is_null($franchise)) {

            $array = array(
            'id' => "", 
            'franchise_name' => "", 
            'created_at' => "", 
            'updated_at' => "", 
            'notes' => "",
            'created_by' => "");

            $json = json_encode($array);
            return response()->json($json);
        }

        $created_by = User::find($franchise->created_by);

        $array = array(
        'id' => $franchise->id, 
        'franchise_name' => $franchise->franchise_name, 
        'created_at' => $franchise->created_at->format('d/m/Y'), 
        'updated_at' => $franchise->updated_at->format('d/m/Y'), 
        'notes' => $franchise->notes,
        'created_by' => $created_by->username);

        $json = json_encode($array);
        return response()->json($json);

    }

    /**
     * Return all franchise data through ajax.
     *
     * @param  int  $id
     * @return Response
     */
    public function franchiseData() {

        $user = \Auth::user();

        $franchises = Franchise::leftJoin('users','franchises.created_by', '=', 'users.id')
        ->select(['franchises.id AS franchise_id', 'franchise_name', 'franchises.created_at AS created_at', 'franchises.notes AS notes', 'users.username AS created_by'])
        ->orderBy('franchises.id', 'asc')
        ->get();

        return Datatables::of($franchises)
        ->addColumn('action', function ($franchise) {

            return '<div class="btn-group"><button type="button" class="open-franchisesModal btn btn-default btn-xs btn-flat" data-id="' . $franchise->franchise_id .'" data-toggle="modal" data-target="#franchisesModal"><i class="fa fa-arrow-right"></i></button></div>';

        })->make(true); 
    }
}
