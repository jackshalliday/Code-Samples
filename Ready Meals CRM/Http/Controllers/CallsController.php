<?php

namespace App\Http\Controllers;

//use Illuminate\Http\Request;
use View;
use Input;
use Request;

use App\Customers;
use App\CustomerTitle;
use App\CustomerCategory;
use App\Franchise;
use App\Area;
use App\Round;
use App\Timeslot;
use App\User;
use App\Activity;

use Yajra\Datatables\Facades\Datatables;

class CallsController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('user_active');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = \Auth::user();
        return View::make('calls.index', compact('user'));
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
    public function store($id)
    {
        $user = \Auth::user();
        return View::make('errors.404', compact('user'));
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
    public function update($id)
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
    public function ajaxCallsModal($customer_number) {

        $customer_number = $customer_number;

        $customer = \DB::table('customers')->where('customer_number', $customer_number)->first();
        $activity = \DB::table('activities')->where('customer_id', $customer->id)->first();

        if(is_null($customer)) {

            $array = array(
            'id' => "", 
            'customer_number' => "", 
            'title_id' => "", 
            'first_name' => "", 
            'surname' => "", 
            'category_id' => "",
            'user_id' => "",
            'telephone' => "",
            'mobile' => "",
            'email' => "",
            'activity_id' => "",
            'activity_title' => "",
            'activity_notes' => "",
            'is_closed' => "");

            $json = json_encode($array);
            return response()->json($json);
        }
        else {

            $category = CustomerCategory::find($customer->category_id);
            $title = CustomerTitle::find($customer->title_id);
            $franchise = Franchise::find($customer->franchise_id);
            $area = Area::find($customer->area_id );
            $round = Round::find($customer->round_id);
            $timeslot = Timeslot::find($customer->timeslot_id);
            $user = User::find($customer->user_id);

            $customer->category_id = $category->name;
            $customer->title_id = $title->name;
            $customer->franchise_id = $franchise->franchise_name;
            $customer->area_id = $area->area_name;
            $customer->round_id = $round->round_name;
            $customer->timeslot_id = $timeslot->timeslot_name;
            $customer->user_id = $user->username;

            $array = array(
            'id' => $customer->id, 
            'customer_number' => $customer->customer_number, 
            'title_id' => $customer->title_id, 
            'first_name' => $customer->first_name, 
            'surname' => $customer->surname, 
            'category_id' => $customer->category_id,
            'user_id' => $customer->user_id,
            'telephone' => $customer->telephone,
            'mobile' => $customer->mobile,
            'email' => $customer->email,
            'next_activity_date' => date("d/m/Y", strtotime($activity->next_activity_date)),
            'activity_id' => $activity->id,
            'activity_title' => $activity->title,
            'activity_notes' => $activity->notes,
            'is_closed' => $customer->is_closed);

            $json = json_encode($array);
            return response()->json($json);
        } 
    }

    /**
     * Update the specified resource through ajax.
     *
     * @param  int  $id
     * @return Response
     */
    public function ajaxUpdateActivity()
    {
        if(Request::ajax()) {

            $customer_number = Input::get('customer_number');
            $customer = \DB::table('customers')->where('customer_number', $customer_number)->first();
            
            $activity = \DB::table('activities')->where('customer_id', $customer->id)->first();
            $activity = Activity::find($activity->id);
            
            $activity->next_activity_date = \DateTime::createFromFormat('d/m/Y', Input::get('next_activity_date'));
            $activity->title = Input::get('title');
            $activity->notes = Input::get('notes');
            $activity->updated_at  = \Carbon\Carbon::now();
            $activity->save();

            die;
        }
    }

    /**
     * Return all calls date through ajax.
     *
     * @param  int  $id
     * @return Response
     */
    public function callsData() {

        $user = \Auth::user();

        if($user->access_level >= 2) {

            $customers = Customers::leftJoin('franchises','customers.franchise_id', '=', 'franchises.id')
            ->leftJoin('customer_categories' , 'customers.category_id', '=', 'customer_categories.id')
            ->leftJoin('activities' , 'customers.id', '=', 'activities.customer_id')
            ->leftJoin('users' , 'customers.user_id', '=', 'users.id')
            ->leftJoin('rounds' , 'customers.round_id', '=', 'rounds.id')
            ->select(['customer_number', 'delivery_day', 'surname', 'activities.title AS activity', 'customer_categories.name AS category', 'franchises.franchise_name AS franchise', 'users.username AS user', 'rounds.round_name AS round'])
            ->where('activities.next_activity_date', '=', \Carbon\Carbon::today()->toDateString())
            ->where('customers.is_closed', '=', 0)
            ->where('customers.receive_calls', '=', 1)
            ->orderBy('delivery_day', 'asc')
            ->get();

        }
        else {

            $customers = Customers::leftJoin('franchises','customers.franchise_id', '=', 'franchises.id')
            ->leftJoin('customer_categories' , 'customers.category_id', '=', 'customer_categories.id')
            ->leftJoin('activities' , 'customers.id', '=', 'activities.customer_id')
            ->leftJoin('users' , 'customers.user_id', '=', 'users.id')
            ->leftJoin('rounds' , 'customers.round_id', '=', 'rounds.id')
            ->select(['customer_number', 'delivery_day', 'surname', 'activities.title AS activity', 'customer_categories.name AS category', 'franchises.franchise_name AS franchise', 'users.username AS user', 'rounds.round_name AS round'])
            ->where('franchises.id', '=', $user->franchise_id)
            ->where('activities.next_activity_date', '=', \Carbon\Carbon::today()->toDateString())
            ->where('customers.is_closed', '=', 0)
            ->where('customers.receive_calls', '=', 1)
            ->orderBy('delivery_day', 'asc')
            ->get();
        }

        
        return Datatables::of($customers)
        ->addColumn('action', function ($customer) {
                //return '<a href="../customer/'.$user->id.'" class="btn btn-xs btn-primary"><i class="glyphicon glyphicon-edit"></i> Edit</a>';
            return '<div class="btn-group"><button type="button" class="open-callsModal btn btn-default btn-xs btn-flat" data-id="' . $customer->customer_number .'" data-toggle="modal" data-target="#callsModal"><i class="fa fa-arrow-right"></i></button></div>';

        })
        ->make(true); 
    }
}
