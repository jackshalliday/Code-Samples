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
use App\Http\Requests\AreaFormRequest;
use App\PaymentTerms;
use App\PrimarySources;
use App\Round;
use App\Timeslot;
use App\User;
use App\OrderStatus;
use App\Order;
use App\Items;
use App\ItemsToFranchise;

class AreaController extends Controller
{
    /**
     * Instantiate a new AreaController instance.
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
            $rounds = Round::pluck('round_name', 'id');
        }
        else {
            $rounds = Round::where('franchise_id', $user->franchise_id)->pluck('round_name', 'id');
        }

        $timeslots = Timeslot::pluck('timeslot_name', 'id');

    	$area = new Area;
        return View::make('area.index', compact('area', 'user', 'rounds', 'timeslots'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
    	//echo "create";
        //\App::abort(404);
        $user = \Auth::user();
        return View::make('errors.404', compact('user'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(AreaFormRequest $request)
    {
    	$user = \Auth::user();

        if($user->access_level >= 2) {
            $rounds = Round::pluck('round_name', 'id');
        }
        else {
            $rounds = Round::where('franchise_id', $user->franchise_id)->pluck('round_name', 'id');
        }
        $timeslots = Timeslot::pluck('timeslot_name', 'id');
    	$area = new Area;

    	if(\Input::get('update')) {

            $area = Area::find($request->area_id);
    	}

        $request->postcode = str_replace(' ', '', $request->postcode);

        $area->postcode = $request->postcode;
        $area->round_id = $request->round_id;
        $area->timeslot_id = $request->timeslot_id;
        $area->area_name = $request->area_name;
        $area->notes = $request->notes;
        $area->created_by = $user->id;

        $area_exists = \DB::table('areas')->where('postcode', $request->postcode)->first();

        if(!is_null($area_exists)) {

            if($area->id != $area_exists->id) {

                \Session::flash('error_message', 'Postcode already exists! Please try again.');
                return View::make('area.index', compact('area', 'user', 'rounds', 'timeslots'));
            }
        }

        $area->save();

        if(\Input::get('update')) {

            \Session::flash('success_message', 'Postcode ' . $area->postcode . ' successfully updated!');
        }
        else {

            \Session::flash('success_message', 'Round ' . $area->postcode . ' successfully created!');
        }

        $area = new Area;
	    return View::make('area.index', compact('area', 'user', 'rounds', 'timeslots')); 
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
    public function update($id, AreaFormRequest $request)
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
    public function ajaxAreaModal($area_id) {

        $area_id = $area_id;
        $user = \Auth::user();
        $area = \DB::table('areas')->where('id', $area_id)->first();

        if(is_null($area)) {

            $array = array(
            'area_name' => "", 
            'postcode' => "", 
            'round_id' => "", 
            'created_by' => "",
            'created_at' => "",
            'updated_at' => "", 
            'notes' => "",
            'timeslot_id' => "");

            $json = json_encode($array);
            return response()->json($json);
        }

        $created_by = User::find($area->created_by);

        $array = array(
            'area_name' => $area->area_name, 
            'postcode' => $area->postcode, 
            'round_id' => $area->round_id, 
            'created_by' => $created_by->username,
            'created_at' => $area->created_at,
            'updated_at' => $area->updated_at,
            'notes' => $area->notes,
            'timeslot_id' => $area->timeslot_id);

        $json = json_encode($array);
        return response()->json($json);
    }

    /**
    * Return all areas data through ajax.
    */
    public function areaData() {

        $user = \Auth::user();

        if($user->access_level >= 2) {

            $areas = Area::leftJoin('rounds','areas.round_id', '=', 'rounds.id')
            ->leftJoin('timeslots','areas.timeslot_id', '=', 'timeslots.id')
            ->leftJoin('franchises','rounds.franchise_id', '=', 'franchises.id')
            ->select(['areas.id AS area_id', 'postcode', 'rounds.round_name AS round_name', 'timeslots.timeslot_name AS timeslot', 'areas.area_name AS area_name', 'franchises.franchise_name AS franchise', 'areas.notes AS notes'])
            ->orderBy('franchises.franchise_name', 'asc')
            ->get();
        }
        else {

            $areas = Area::leftJoin('rounds','areas.round_id', '=', 'rounds.id')
            ->leftJoin('timeslots','areas.timeslot_id', '=', 'timeslots.id')
            ->leftJoin('franchises','rounds.franchise_id', '=', 'franchises.id')
            ->select(['areas.id AS area_id', 'postcode', 'rounds.round_name AS round_name', 'timeslots.timeslot_name AS timeslot', 'areas.area_name AS area_name', 'franchises.franchise_name AS franchise', 'areas.notes AS notes'])
            ->where('rounds.franchise_id', '=', $user->franchise_id)
            ->orderBy('franchises.franchise_name', 'asc')
            ->get();
        }

        return Datatables::of($areas)
        ->addColumn('action', function ($area) {

            return '<div class="btn-group"><button type="button" class="open-areasModal btn btn-default btn-xs btn-flat" data-id="' . $area->area_id .'" data-toggle="modal" data-target="#areasModal"><i class="fa fa-arrow-right"></i></button></div>';

        })->make(true); 
    }
}
