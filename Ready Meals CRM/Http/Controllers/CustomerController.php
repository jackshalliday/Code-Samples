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
use App\Http\Requests\CustomerFormRequest;
use App\PaymentTerms;
use App\PrimarySources;
use App\Round;
use App\Timeslot;
use App\User;
use App\OrderStatus;
use App\Order;

class CustomerController extends Controller
{
    /**
     * Instantiate a new CustomerController instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('user_active');
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $user = \Auth::user();
        return View::make('customer.index', compact('user'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        $user = \Auth::user();

        $titles = CustomerTitle::pluck('name', 'id')->prepend('Please select','');
        $users = User::pluck('username', 'id');
        $categories = \DB::table('customer_categories')->where('name', 'ENQ')->first();
        $paymentTerms = PaymentTerms::pluck('name', 'id'); 
        $primarySources = PrimarySources::pluck('name', 'id'); 

        $prev_customer_record = \DB::table('customers')->latest()->first();

        $customer = new Customers;

        $customer->category_id = $categories->name;
        $customer->customer_number = $prev_customer_record->customer_number + 1;
        $customer->user_id = $user->id;
        $customer->created_by = $user->id;

        $customer->is_billing_same = true;
        $customer->receive_email = true;
        $customer->receive_calls = true;
        $customer->receive_post = true;

        return View::make('customer.create', compact('titles', 'users', 'paymentTerms', 'primarySources', 'customer', 'user'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(CustomerFormRequest $request)
    {
        $user = \Auth::user();

        $customer = new Customers;

        $prev_customer_record = \DB::table('customers')->latest()->first();
        $customer->customer_number = $prev_customer_record->customer_number + 1;

        $customer->title_id = $request->title_id;
        $customer->first_name = $request->first_name;
        $customer->surname = $request->surname;
        $customer->telephone = $request->telephone;
        $customer->mobile = $request->mobile;
        $customer->email = $request->email;
        
        $category = \DB::table('customer_categories')->where('name', $request->category_id)->first();
        $customer->category_id = $category->id;

        $customer->created_at  = \Carbon\Carbon::now()->toDateTimeString();
        $customer->updated_at  = \Carbon\Carbon::now()->toDateTimeString();

        $customer->user_id = $request->user_id;
        $customer->address_postcode = $request->address_postcode;
        $customer->address_1 = $request->address_1;
        $customer->address_2 = $request->address_2;
        $customer->address_town = $request->address_town;
        $customer->address_county = $request->address_county;

        $franchise = \DB::table('franchises')->where('franchise_name', $request->franchise_id)->first();
        $customer->franchise_id = $franchise->id;

        $area = \DB::table('areas')->where('postcode', $request->area_id)->first();
        $customer->area_id = $area->id;

        $customer->delivery_day = $request->delivery_day;

        $round = \DB::table('rounds')->where('round_name', $request->round_id)->first();
        $customer->round_id = $round->id;

        $timeslot = \DB::table('timeslots')->where('timeslot_name', $request->timeslot_id)->first();
        $customer->timeslot_id = $timeslot->id;

        $customer->driver_notes = $request->driver_notes;
        $customer->delivery_notes = $request->delivery_notes;

        $customer->billing_1 = $request->billing_1;
        $customer->billing_2 = $request->billing_2;
        $customer->billing_town = $request->billing_town;
        $customer->billing_county = $request->billing_county;
        $customer->billing_postcode = $request->billing_postcode;
        $customer->payterm_id = $request->payterm_id;

        $customer->is_billing_same = false;
        if($request->exists('is_billing_same')) {
            $customer->is_billing_same = true;

            $customer->billing_1 = $request->address_1;
            $customer->billing_2 = $request->address_2;
            $customer->billing_town = $request->address_town;
            $customer->billing_county = $request->address_county;
            $customer->billing_postcode = $request->address_postcode;   
        }

        $customer->payterm_id = $request->payterm_id;
        
        $customer->receive_post = false;
        if(\Input::get('receive_post') != null) {
            $customer->receive_post = true;
        }
        
        $customer->receive_calls = false;
        if(\Input::get('receive_calls') != null) {
            $customer->receive_calls = true;
        }
        
        $customer->receive_email = false;
        if(\Input::get('receive_email') != null) {
            $customer->receive_email = true;
        }
        
        $customer->primarysource_id = $request->primarysource_id;
        $customer->is_closed = false;

        $customer->created_by = $user->id;

        $customer->save();


        $customer_session_array = array();
        $customer_in_session = false;

        if(\Session::has('customer_session')) {

            $customer_session_array = \Session::get('customer_session');
        }

        \Session::forget('customer_session');

        foreach($customer_session_array as $array_item) {

            if($customer->id == $array_item[0]) {

                $customer_in_session = true;
                $array_item[3] = true;
            }
            else {

                $array_item[3] = false;
            }

            \Session::push('customer_session', $array_item);
        }

        if(!$customer_in_session) {

            $customer_session = array($customer->id, $customer->first_name, $customer->surname, true);
            \Session::push('customer_session', $customer_session);
            
        } 

        $lastInsertedId= $customer->id;

        $activity = new Activity;

        $activity->customer_id = $lastInsertedId;
        $activity->next_activity_date = \DateTime::createFromFormat('d/m/Y', \Carbon\Carbon::now()->format('d/m/Y'));
        $activity->created_at  = \Carbon\Carbon::now();
        $activity->updated_at  = \Carbon\Carbon::now();

        $activity->save();

        $activityId = $activity->id;

        \Session::flash('success_message', 'Customer sucessfully created!');
        \Session::flash('warning_message', 'Please update the customer activity form');

        //return \Redirect::route('customer.edit', array($lastInsertedId));
        return \Redirect::route('activity.edit', array($activityId));
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
        $customer = Customers::find($id);

        $customer_session_array = array();
        $customer_in_session = false;

        if(\Session::has('customer_session')) {

            $customer_session_array = \Session::get('customer_session');
        }

        \Session::forget('customer_session');

        foreach($customer_session_array as $array_item) {

            if($customer->id == $array_item[0]) {

                $customer_in_session = true;
                $array_item[3] = true;
            }
            else {

                $array_item[3] = false;
            }

            \Session::push('customer_session', $array_item);
        }

        if(!$customer_in_session) {

            $customer_session = array($customer->id, $customer->first_name, $customer->surname, true);
            \Session::push('customer_session', $customer_session);
            
        } 

        if($customer->is_closed) {

            return View::make('customer.show', compact('customer', 'user'));
        }

        return \Redirect::route('customer.edit', array($id));
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
        $customer = Customers::find($id);

        $customer_session_array = array();
        $customer_in_session = false;

        if(\Session::has('customer_session')) {

            $customer_session_array = \Session::get('customer_session');
        }

        \Session::forget('customer_session');

        foreach($customer_session_array as $array_item) {

            if($customer->id == $array_item[0]) {

                $customer_in_session = true;
                $array_item[3] = true;
            }
            else {

                $array_item[3] = false;
            }

            \Session::push('customer_session', $array_item);
        }

        if(!$customer_in_session) {

            $customer_session = array($customer->id, $customer->first_name, $customer->surname, true);
            \Session::push('customer_session', $customer_session);
            
        } 

        if($customer->is_closed) {

            return \Redirect::route('customer.show', array($id));
        }

        $titles = CustomerTitle::pluck('name', 'id')->prepend('Please select','');
        $users = User::pluck('username', 'id');
        $paymentTerms = PaymentTerms::pluck('name', 'id'); 
        $primarySources = PrimarySources::pluck('name', 'id'); 

        return View::make('customer.edit', compact('titles', 'users', 'paymentTerms', 'primarySources', 'customer', 'user'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function update($id, CustomerFormRequest $request)
    {
        $customer = Customers::find($id);
        $customer->updated_at  = \Carbon\Carbon::now()->toDateTimeString();

        if(\Input::get('open')) {

            $customer->is_closed = false;
            $customer->save();

            // redirect
            \Session::flash('success_message', 'Successfully opened the Customer!');
            return \Redirect::route('customer.edit', array($id));
        }

        $customer->title_id = $request->title_id;
        $customer->first_name = $request->first_name;
        $customer->surname = $request->surname;
        $customer->telephone = $request->telephone;
        $customer->mobile = $request->mobile;
        $customer->email = $request->email;

        $customer->user_id = $request->user_id;
        $customer->address_postcode = $request->address_postcode;
        $customer->address_1 = $request->address_1;
        $customer->address_2 = $request->address_2;
        $customer->address_town = $request->address_town;
        $customer->address_county = $request->address_county;

        $franchise = \DB::table('franchises')->where('franchise_name', $request->franchise_id)->first();
        $customer->franchise_id = $franchise->id;

        $area = \DB::table('areas')->where('postcode', $request->area_id)->first();
        $customer->area_id = $area->id;

        $customer->delivery_day = $request->delivery_day;

        $round = \DB::table('rounds')->where('round_name', $request->round_id)->first();
        $customer->round_id = $round->id;

        $timeslot = \DB::table('timeslots')->where('timeslot_name', $request->timeslot_id)->first();
        $customer->timeslot_id = $timeslot->id;

        $customer->driver_notes = $request->driver_notes;
        $customer->delivery_notes = $request->delivery_notes;

        $customer->billing_1 = $request->billing_1;
        $customer->billing_2 = $request->billing_2;
        $customer->billing_town = $request->billing_town;
        $customer->billing_county = $request->billing_county;
        $customer->billing_postcode = $request->billing_postcode;
        $customer->payterm_id = $request->payterm_id;

        $customer->is_billing_same = false;
        if($request->exists('is_billing_same')) {
            $customer->is_billing_same = true;

            $customer->billing_1 = $request->address_1;
            $customer->billing_2 = $request->address_2;
            $customer->billing_town = $request->address_town;
            $customer->billing_county = $request->address_county;
            $customer->billing_postcode = $request->address_postcode;  
        }

        $customer->payterm_id = $request->payterm_id;
        
        $customer->receive_post = false;
        if(\Input::get('receive_post') != null) {
            $customer->receive_post = true;
        }
        
        $customer->receive_calls = false;
        if(\Input::get('receive_calls') != null) {
            $customer->receive_calls = true;
        }
        
        $customer->receive_email = false;
        if(\Input::get('receive_email') != null) {
            $customer->receive_email = true;
        }
        
        $customer->primarysource_id = $request->primarysource_id;

        if(\Input::get('close')) {

            $customer->is_closed = true;
            $customer->save();

            // redirect
            \Session::flash('success_message', 'Successfully closed the Customer!');
            return \Redirect::route('customer.show', array($id));
        }

        if(\Input::get('update')) {

            $customer->save();

            // redirect
            \Session::flash('success_message', 'Successfully updated the Customer!');
            return \Redirect::route('customer.edit', compact(array($id), 'titles', 'users', 'paymentTerms', 'primarySources', 'customer'));
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        //echo "destroy";
        //\App::abort(404);
        $user = \Auth::user();
        return View::make('errors.404', compact('user'));
    }

    /**
     * Add the specified resource to Active Customer List.
     *
     * @param  int  $id
     * @return Response
     */
    public function ajaxActiveCustomer($customer_id) {

        $customer_id = $customer_id;

        $customer = Customers::find($customer_id);
        $customer_session_array = array();

        if(\Session::has('customer_session')) {

            $customer_session_array = \Session::get('customer_session');
        }

        \Session::forget('customer_session');

        foreach($customer_session_array as $key=>$array_item) {

            if($customer->id != $array_item[0]) {

                \Session::push('customer_session', $array_item);
            } 
        }

        return 1;
    }

    /**
     * Return the specified resource through ajax.
     *
     * @param  int  $id
     * @return Response
     */
    public function ajaxPostcode($postcode) {

        $postcode = $postcode;

        $find_postcode = Postcode::lookup($postcode); //->get();

        $json = json_encode($find_postcode);

        return response()->json($json);
    }

    /**
     * Return the specified resource through ajax.
     *
     * @param  int  $id
     * @return Response
     */
    public function ajaxShipping($postcode) {

        $postcode =  $postcode;

        $postcodeObj = \DB::table('areas')->where('postcode', $postcode)->orWhere('postcode', 'like', '%' . $postcode . '%')->orderBy('postcode', 'asc')->first();

        while($postcodeObj === null) {

            $postcode = substr($postcode, 0, -1);

            $postcodeObj = \DB::table('areas')->where('postcode', $postcode)->orWhere('postcode', 'like', '%' . $postcode . '%')->orderBy('postcode', 'asc')->first();
        }

        $area = Area::find($postcodeObj->id);
        $round = Round::find($area->round_id);
        $timeslot = Timeslot::find($area->timeslot_id);
        $franchise = Franchise::find($round->franchise_id);
 
        $array = array(
            'franchise' => $franchise->franchise_name, 
            'area' => $area->postcode, 
            'day' => $round->round_day, 
            'round' => $round->round_name, 
            'timeslot' => $timeslot->timeslot_name);

        $json = json_encode($array);

        return response()->json($json);
    }

    /**
     * Return all customer data through ajax.
     *
     * @param  int  $id
     * @return Response
     */
    public function customerData() {

        $user = \Auth::user();

        if($user->access_level >= 2) {

            $customers = Customers::leftJoin('franchises','customers.franchise_id', '=', 'franchises.id')
            ->leftJoin('customer_categories' , 'customers.category_id', '=', 'customer_categories.id')
            ->leftJoin('rounds' , 'customers.round_id', '=', 'rounds.id')
            ->leftJoin('users' , 'customers.user_id', '=', 'users.id')
            ->leftJoin('customer_status' , 'customers.is_closed', '=', 'customer_status.id')
            ->leftJoin('activities' , 'customers.id', '=', 'activities.customer_id')
            ->select(['customers.id', 'customer_number', 'first_name', 'surname', 'address_postcode', 'rounds.round_name AS round', 'customer_categories.name AS category', 'activities.next_activity_date AS activity', 'users.username AS user', 'franchises.franchise_name AS franchise', 'customer_status.name AS status'])
            ->orderBy('customers.created_at', 'desc')
            ->get();
        }
        else {

            $customers = Customers::leftJoin('franchises','customers.franchise_id', '=', 'franchises.id')
            ->leftJoin('customer_categories' , 'customers.category_id', '=', 'customer_categories.id')
            ->leftJoin('rounds' , 'customers.round_id', '=', 'rounds.id')
            ->leftJoin('users' , 'customers.user_id', '=', 'users.id')
            ->leftJoin('customer_status' , 'customers.is_closed', '=', 'customer_status.id')
            ->leftJoin('activities' , 'customers.id', '=', 'activities.customer_id')
            ->select(['customers.id', 'customer_number', 'first_name', 'surname', 'address_postcode', 'rounds.round_name AS round', 'customer_categories.name AS category', 'activities.next_activity_date AS activity', 'users.username AS user', 'franchises.franchise_name AS franchise', 'customer_status.name AS status'])
            ->where('customers.franchise_id', '=', $user->franchise_id)
            ->orderBy('customers.created_at', 'desc')
            ->get();

        }

        return Datatables::of($customers)
        ->addColumn('action', function ($customer) {
            return '<div class="btn-group"><button type="button" class="open-customerModal btn btn-default btn-xs btn-flat" data-id="' . $customer->customer_number .'" data-toggle="modal" data-target="#customerModal"><i class="fa fa-arrow-right"></i></button></div>';
        })
        ->make(true); 
    }

    /**
     * Return a customer's order history through ajax.
     *
     * @param  int  $id
     * @return Response
     */
    public function orderHistoryData($customer_number) {

        $customer_number =  $customer_number;
        $customer = \DB::table('customers')->where('customer_number', $customer_number)->first();

        $user = \Auth::user();

        $orders = Order::leftJoin('customers','orders.customer_id', '=', 'customers.id')
        ->leftJoin('users' , 'orders.user_id', '=', 'users.id')
        ->leftJoin('franchises' , 'orders.franchise_id', '=', 'franchises.id')
        ->leftJoin('order_status' , 'orders.status_id', '=', 'order_status.id')
        ->select(['orders.id', 'order_number', 'customers.customer_number AS customer_number', 'customers.surname AS surname', 'total', 'delivery_date', 'orders.created_at AS created_at', 'users.name AS user', 'franchises.franchise_name AS franchise', 'order_status.name AS status'])
        ->where('orders.franchise_id', '=', $user->franchise_id)
        ->where('customers.id', '=', $customer->id)
        ->orderBy('orders.created_at', 'desc')
        ->get();

        if($customer->is_closed) {
            return Datatables::of($orders)
            ->addColumn('action', function ($order) {
                return '<div class="btn-group"><button type="button" class="open-orderModal btn btn-default btn-xs btn-flat" data-id="' . $order->order_number .'" data-toggle="modal" data-target="#orderModal" disabled><i class="fa fa-arrow-right"></i></button></div>';
            })->make(true); 

        }
        else {
            return Datatables::of($orders)
            ->addColumn('action', function ($order) {
                return '<div class="btn-group"><button type="button" class="open-orderModal btn btn-default btn-xs btn-flat" data-id="' . $order->order_number .'" data-toggle="modal" data-target="#orderModal"><i class="fa fa-arrow-right"></i></button></div>';
            })->make(true); 
        }   
    }
}
