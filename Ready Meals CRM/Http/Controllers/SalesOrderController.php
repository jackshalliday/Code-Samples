<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use View;

use App\PaymentTerms;
use App\Items;
use App\ItemsToOrder;
use App\Customers;
use App\Order;
use App\CustomerCategory;
use App\CustomerTitle;
use App\Franchise;
use App\Area;
use App\Round;
use App\Timeslot;
use App\OrderStatus;
use App\User;
use App\ItemsToFranchise;

use Yajra\Datatables\Facades\Datatables;
use App\Http\Requests\OrderFormRequest;
use Illuminate\Support\Facades\DB;

class SalesOrderController extends Controller
{
    /**
     * Instantiate a new SalesOrderController instance.
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
        return View::make('sales_order.index', compact('user'));  
    }
 
    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        $user = \Auth::user();

        $customer = new Customers;
    	$paymentTerms = PaymentTerms::pluck('name', 'id');
        $sales_order = new Order;

        $last_order_created = \DB::table('orders')->latest()->first();
        $sales_order->order_number = $last_order_created->order_number + 1;
        $status_open = \DB::table('order_status')->where('name', 'OPEN')->first();
        $sales_order->status_id = $status_open->id;
        $sales_order->user_id = $user->id;
        $sales_order->created_at = \Carbon\Carbon::now();

    	return View::make('sales_order.create', compact('sales_order', 'paymentTerms', 'customer', 'user'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(OrderFormRequest $request)
    {
        $user = \Auth::user();
        $sales_order = new Order;

        $prev_order_record = \DB::table('orders')->latest()->first();
        $sales_order->order_number = $prev_order_record->order_number + 1;

        $sales_order->status_id = 1;
        $sales_order->user_id = $user->id;

        $sales_order->delivery_date = \DateTime::createFromFormat('d/m/Y', $request->delivery_date);

        $customer = \DB::table('customers')->where('customer_number', $request->customer_number)->first();

        if (is_null($customer)) {
            
            return \Redirect::back()->with('error_message','Error processing order! Invalid Customer Number.');
        }

        if ($customer->is_closed) {
            
            return \Redirect::back()->with('error_message','Error processing order! Customer Account is closed.');
        }

        $sales_order->customer_id = $customer->id;

        if(!$request->shipping_address == "") {

            $arr = explode("\n", $request->shipping_address);

            $sales_order->address_1 = $arr[0];
            $sales_order->address_2 = $arr[1];
            $sales_order->address_town = $arr[2];
            $sales_order->address_county = $arr[3];
            $sales_order->address_postcode = $arr[4];
        }
        else {

            $sales_order->address_1 = $customer->address_1;
            $sales_order->address_2 = $customer->address_2;
            $sales_order->address_town = $customer->address_town;
            $sales_order->address_county = $customer->address_county;
            $sales_order->address_postcode = $customer->address_postcode;
        }

        $sales_order->billing_1 = $customer->billing_1;
        $sales_order->billing_2 = $customer->billing_2;
        $sales_order->billing_town = $customer->billing_town;
        $sales_order->billing_county = $customer->billing_county;
        $sales_order->billing_postcode = $customer->billing_postcode;

        $franchise = \DB::table('franchises')->where('franchise_name', $request->franchise_id)->first();
        $sales_order->franchise_id = $franchise->id;

        $area = \DB::table('areas')->where('postcode', $request->area_id)->first();
        $sales_order->area_id = $area->id;

        $sales_order->delivery_day = $request->delivery_day;

        $round = \DB::table('rounds')->where('round_name', $request->round_id)->first();
        $sales_order->round_id = $round->id;

        $timeslot = \DB::table('timeslots')->where('timeslot_name', $request->timeslot_id)->first();
        $sales_order->timeslot_id = $timeslot->id;

        $sales_order->driver_notes = $request->driver_notes;
        $sales_order->delivery_notes = $request->delivery_notes;
        $sales_order->before_discount = $request->before_discount;
        $sales_order->discount = $request->discount;
        $sales_order->delivery_charge = $request->delivery_charge;
        $sales_order->total = $request->order_total;
        $sales_order->payterm_id = $request->payterm_id;
        $sales_order->is_closed = 0;

        if($request->total_items < 1) {

            return \Redirect::back()->with('error_message','Error creating order. This order has 0 Items');
        }

        $sales_order->total_items = $request->total_items;

        $sales_order->save();
        $lastInsertedId = $sales_order->id;

        for ($i = 0; $i < sizeof($request->item_number); $i++) {

            if(!is_null($request->item_number[$i])) {
                  
                $itemsToOrder = new ItemsToOrder;

                $itemsToOrder->order_id = $lastInsertedId;

                $item = \DB::table('items')->where('item_number', $request->item_number[$i])->first();
                $itemsToOrder->item_id = $item->id;

                $itemsToOrder->quantity = $request->quantity[$i];
                $itemsToOrder->unit_price = $request->unit_price[$i];

                $sales_order->created_at  = \Carbon\Carbon::now();
                $sales_order->updated_at  = \Carbon\Carbon::now();

                $itemsToOrder->substitute_id = $request->substitute_id[$i];

                $itemsToOrder->save();  

                $query = \DB::table('items_to_franchises')
                ->where('item_id', $item->id)
                ->where('franchise_id', $user->franchise_id);

                $query->decrement('stock', $request->quantity[$i]);
                $query->increment('committed', $request->quantity[$i]);
            }  
        } 

        $activityId = $sales_order->customer()->first()->activity()->first()->id;

        if($request->shipping_address == "") {
            \Session::flash('success_message', 'Order sucessfully created! Note: Shipping address was empty. Order updated with Customer default Shipping Address');
        }
        else {
            \Session::flash('success_message', 'Order sucessfully created!');
        }

        \Session::flash('warning_message', 'Please update the customer activity form');

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

        $sales_order = Order::find($id);

        $paymentTerms = PaymentTerms::pluck('name', 'id'); 
        $customer = $sales_order->customer()->first();

        $sales_order->delivery_date = date('d/m/Y', strtotime($sales_order->delivery_date));

        $sales_order->address_1 = $sales_order->address_1;
        $sales_order->address_1 .= $sales_order->address_2;
        $sales_order->address_1 .= $sales_order->address_town;
        $sales_order->address_1 .= $sales_order->address_county;
        $sales_order->address_1 .= $sales_order->address_postcode;

        $sales_order->billing_1 = $sales_order->billing_1 . "\n";
        $sales_order->billing_1 .= $sales_order->billing_2 . "\n";
        $sales_order->billing_1 .= $sales_order->billing_town . "\n";
        $sales_order->billing_1 .= $sales_order->billing_county . "\n";
        $sales_order->billing_1 .= $sales_order->billing_postcode;

        if($sales_order->is_closed) {

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

            return View::make('sales_order.show', compact('paymentTerms', 'sales_order', 'customer', 'user'));
        }

        return \Redirect::route('sales_order.edit', array($id));
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
        $sales_order = Order::find($id);

        if($sales_order->is_closed) {

            return \Redirect::route('sales_order.show', array($id));
        }
        
        $paymentTerms = PaymentTerms::pluck('name', 'id'); 
        $customer = $sales_order->customer()->first();

        $sales_order->delivery_date = date('d/m/Y', strtotime($sales_order->delivery_date));

        $sales_order->address_1 = $sales_order->address_1 . "\n";
        $sales_order->address_1 .= $sales_order->address_2 . "\n";
        $sales_order->address_1 .= $sales_order->address_town . "\n";
        $sales_order->address_1 .= $sales_order->address_county . "\n";
        $sales_order->address_1 .= $sales_order->address_postcode;

        $sales_order->billing_1 = $sales_order->billing_1 . "\n";
        $sales_order->billing_1 .= $sales_order->billing_2 . "\n";
        $sales_order->billing_1 .= $sales_order->billing_town . "\n";
        $sales_order->billing_1 .= $sales_order->billing_county . "\n";
        $sales_order->billing_1 .= $sales_order->billing_postcode;

        return View::make('sales_order.edit', compact('paymentTerms', 'sales_order', 'customer', 'user'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function update($id, OrderFormRequest $request)
    {
        $user = \Auth::user();
        $paymentTerms = PaymentTerms::pluck('name', 'id'); 

        $sales_order = Order::find($id);
        $sales_order_id = $sales_order->id;

        $sales_order->updated_at  = \Carbon\Carbon::now()->toDateTimeString();

        if(\Input::get('open')) {

            $sales_order->is_closed = false;
            $sales_order->save();

            // redirect
            \Session::flash('success_message', 'Successfully opened the Sales Order!');
            return \Redirect::route('sales_order.edit', array($sales_order_id));
        }

        $sales_order->delivery_date = \DateTime::createFromFormat('d/m/Y', $request->delivery_date);

        $customer = \DB::table('customers')->where('customer_number', $request->customer_number)->first();

        if (is_null($customer)) {
            
            return \Redirect::back()->with('error_message','Invalid Customer Number');
        }

        $sales_order->customer_id = $customer->id;

        if(!$request->shipping_address == "") {

            $arr = explode("\n", $request->shipping_address);

            $sales_order->address_1 = $arr[0];
            $sales_order->address_2 = $arr[1];
            $sales_order->address_town = $arr[2];
            $sales_order->address_county = $arr[3];
            $sales_order->address_postcode = $arr[4];
        }

        $sales_order->billing_1 = $customer->billing_1;
        $sales_order->billing_2 = $customer->billing_2;
        $sales_order->billing_town = $customer->billing_town;
        $sales_order->billing_county = $customer->billing_county;
        $sales_order->billing_postcode = $customer->billing_postcode;

        $franchise = \DB::table('franchises')->where('franchise_name', $request->franchise_id)->first();
        $sales_order->franchise_id = $franchise->id;

        $area = \DB::table('areas')->where('postcode', $request->area_id)->first();
        $sales_order->area_id = $area->id;

        $sales_order->delivery_day = $request->delivery_day;

        $round = \DB::table('rounds')->where('round_name', $request->round_id)->first();
        $sales_order->round_id = $round->id;

        $timeslot = \DB::table('timeslots')->where('timeslot_name', $request->timeslot_id)->first();
        $sales_order->timeslot_id = $timeslot->id;

        $sales_order->driver_notes = $request->driver_notes;
        $sales_order->delivery_notes = $request->delivery_notes;
        $sales_order->before_discount = $request->before_discount;
        $sales_order->discount = $request->discount;
        $sales_order->delivery_charge = $request->delivery_charge;
        $sales_order->total = $request->order_total;
        $sales_order->payterm_id = $request->payterm_id;

        if($request->total_items < 1 && \Input::get('update')) {

            return \Redirect::back()->with('error_message','Error updating order. This order has 0 Items');
        }

        $sales_order->total_items = $request->total_items;

        $sales_order->save();
        
        $lastInsertedId = $sales_order->id;

        $current_items = \DB::table('items_to_orders')->where('order_id', $lastInsertedId)->get();

        foreach ($current_items as $item) {

            $query = \DB::table('items_to_franchises')
                ->where('item_id', $item->item_id)
                ->where('franchise_id', $user->franchise_id);

            $query->increment('stock', $item->quantity);
            $query->decrement('committed', $item->quantity);
        }

        \DB::table('items_to_orders')->where('order_id', $lastInsertedId)->delete();

        for ($i = 0; $i < sizeof($request->item_number); $i++) {

            if(!is_null($request->item_number[$i])) {
                  
                $itemsToOrder = new ItemsToOrder;

                $itemsToOrder->order_id = $lastInsertedId;

                $item = \DB::table('items')->where('item_number', $request->item_number[$i])->first();
                $itemsToOrder->item_id = $item->id;

                $itemsToOrder->quantity = $request->quantity[$i];
                $itemsToOrder->unit_price = $request->unit_price[$i];

                $sales_order->created_at  = \Carbon\Carbon::now();
                $sales_order->updated_at  = \Carbon\Carbon::now();

                $itemsToOrder->substitute_id = $request->substitute_id[$i];

                $itemsToOrder->save();  

                $query = \DB::table('items_to_franchises')
                ->where('item_id', $item->id)
                ->where('franchise_id', $user->franchise_id);

                $query->decrement('stock', $request->quantity[$i]);
                $query->increment('committed', $request->quantity[$i]);
            }  
        }

        if(\Input::get('cancel')) {

            $current_items = \DB::table('items_to_orders')->where('order_id', $lastInsertedId)->get();

            foreach ($current_items as $item) {

                $query = \DB::table('items_to_franchises')
                    ->where('item_id', $item->item_id)
                    ->where('franchise_id', $user->franchise_id);

                $query->increment('stock', $item->quantity);
                $query->decrement('committed', $item->quantity);
            }

            $status_cancelled = \DB::table('order_status')->where('name', 'CANCELLED')->first();
            $sales_order->status_id = $status_cancelled->id;

            $sales_order->is_closed = true;
            $sales_order->save();

            // redirect
            \Session::flash('success_message', 'Successfully cancelled the Order!');
            return \Redirect::route('sales_order.show', array($sales_order_id));
        }

        if(\Input::get('update')) {

            $sales_order->save();

            if($request->shipping_address == "") {

                \Session::flash('warning_message', 'Shipping Address cannot be empty! Reverted to previous valid address.');
            }

            // redirect
            \Session::flash('success_message', 'Successfully updated the Order!');
            return \Redirect::route('sales_order.edit', compact('sales_order_id'));
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
        $user = \Auth::user();
        return View::make('errors.404', compact('user'));
    }

    /**
     * Return new Sales Order with customer data.
     *
     * @param  int  $id
     * @return Response
     */
    public function withCustomer($id) {

        $user = \Auth::user();
        
        $sales_order = new Order;
        $paymentTerms = PaymentTerms::pluck('name', 'id'); 
        $customer = Customers::find($id);

        $last_order_created = \DB::table('orders')->latest()->first();
        $sales_order->order_number = $last_order_created->order_number + 1;
        $status_open = \DB::table('order_status')->where('name', 'OPEN')->first();
        $sales_order->status_id = $status_open->id;
        $sales_order->user_id = $user->id;
        $delivery_date = \Carbon\Carbon::parse('next ' . $customer->delivery_day);
        $sales_order->delivery_date = $delivery_date->format('d/m/Y');
        $sales_order->created_at = \Carbon\Carbon::now();

        $sales_order->address_1 = $customer->address_1 . "\n";
        $sales_order->address_1 .= $customer->address_2 . "\n";
        $sales_order->address_1 .= $customer->address_town . "\n";
        $sales_order->address_1 .= $customer->address_county . "\n";
        $sales_order->address_1 .= $customer->address_postcode;

        $sales_order->billing_1 = $customer->billing_1 . "\n";
        $sales_order->billing_1 .= $customer->billing_2 . "\n";
        $sales_order->billing_1 .= $customer->billing_town . "\n";
        $sales_order->billing_1 .= $customer->billing_county . "\n";
        $sales_order->billing_1 .= $customer->billing_postcode;

        $category = CustomerCategory::find($customer->category_id);
        $title = CustomerTitle::find($customer->title_id);
        $franchise = Franchise::find($customer->franchise_id);
        $area = Area::find($customer->area_id );
        $round = Round::find($customer->round_id);
        $timeslot = Timeslot::find($customer->timeslot_id);

        $customer->category_id = $category->name;

        if($customer->is_closed) {

            $customer->category_id = "CLSD-" . $category->name;
        }
    
        $customer->title_id = $title->name;
        $customer->franchise_id = $franchise->franchise_name;
        $customer->area_id = $area->postcode;
        $customer->round_id = $round->round_name;
        $customer->timeslot_id = $timeslot->timeslot_name;

        return View::make('sales_order.create', compact('sales_order', 'paymentTerms', 'customer', 'user'));
    }

    /**
     * Return a duplicated Sales Order
     *
     * @param  int  $id
     * @return Response
     */
    public function duplicate($id)
    {
        $user = \Auth::user();

        $sales_order = Order::find($id);
        $customer = \DB::table('customers')->where('id', $sales_order->customer_id)->first();
        $paymentTerms = PaymentTerms::pluck('name', 'id');

        $last_order_created = \DB::table('orders')->latest()->first();
        $sales_order->order_number = $last_order_created->order_number + 1;
        $status_open = \DB::table('order_status')->where('name', 'OPEN')->first();
        $sales_order->status_id = $status_open->id;
        $sales_order->user_id = $user->id;
        $sales_order->created_at = \Carbon\Carbon::now();

        $delivery_date = \Carbon\Carbon::parse('next ' . $customer->delivery_day);
        $sales_order->delivery_date = $delivery_date->format('d/m/Y');

        $sales_order->address_1 = $sales_order->address_1 . "\n";
        $sales_order->address_1 .= $sales_order->address_2 . "\n";
        $sales_order->address_1 .= $sales_order->address_town . "\n";
        $sales_order->address_1 .= $sales_order->address_county . "\n";
        $sales_order->address_1 .= $sales_order->address_postcode;

        $sales_order->billing_1 = $sales_order->billing_1 . "\n";
        $sales_order->billing_1 .= $sales_order->billing_2 . "\n";
        $sales_order->billing_1 .= $sales_order->billing_town . "\n";
        $sales_order->billing_1 .= $sales_order->billing_county . "\n";
        $sales_order->billing_1 .= $sales_order->billing_postcode;

        $category = CustomerCategory::find($customer->category_id);
        $title = CustomerTitle::find($customer->title_id);
        $franchise = Franchise::find($customer->franchise_id);
        $area = Area::find($customer->area_id );
        $round = Round::find($customer->round_id);
        $timeslot = Timeslot::find($customer->timeslot_id);

        $customer->category_id = $category->name;
        $customer->title_id = $title->name;
        $customer->franchise_id = $franchise->franchise_name;
        $customer->area_id = $area->postcode;
        $customer->round_id = $round->round_name;
        $customer->timeslot_id = $timeslot->timeslot_name;

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

        return View::make('sales_order.duplicate', compact('sales_order', 'paymentTerms', 'customer', 'user'));
    }

    /**
     * Return the specified resource through ajax.
     *
     * @param  int  $id
     * @return Response
     */
    public function ajaxCustomer($customer_number) {

        $customer_number = $customer_number;

        $customer = \DB::table('customers')->where('customer_number', $customer_number)->first();

        if(is_null($customer)) {

            $customer = new Customers;
        }
        else {

            $category = CustomerCategory::find($customer->category_id);
            $title = CustomerTitle::find($customer->title_id);
            $franchise = Franchise::find($customer->franchise_id);
            $area = Area::find($customer->area_id );
            $round = Round::find($customer->round_id);
            $timeslot = Timeslot::find($customer->timeslot_id);

            $customer->category_id = $category->name;

            if($customer->is_closed) {
                $customer->category_id = "CLSD-" . $category->name;
            }

            $customer->title_id = $title->name;
            $customer->franchise_id = $franchise->franchise_name;
            $customer->area_id = $area->postcode;
            $customer->round_id = $round->round_name;
            $customer->timeslot_id = $timeslot->timeslot_name;
        }

        $json = json_encode($customer);

        return response()->json($json);
    }

    /**
     * Return the specified resource through ajax.
     *
     * @param  int  $id
     * @return Response
     */
    public function ajaxOrder($customer_number) {

        $user = \Auth::user();

        $customer_number = $customer_number;

        $last_order_created = \DB::table('orders')->latest()->first();
        $order_number = $last_order_created->order_number + 1;

        $web_number = "";

        $status_open = \DB::table('order_status')->where('name', 'OPEN')->first();
        $status_id = $status_open->name;

        $user_id = $user->name;

        $customer = \DB::table('customers')->where('customer_number', $customer_number)->first();

        $delivery_date = \Carbon\Carbon::parse('next ' . $customer->delivery_day);
        $delivery_date = $delivery_date->format('d/m/Y');

        $created_at = \Carbon\Carbon::now();
        $created_at = $created_at->format('d/m/Y');

       	$array = array(
            'order_number' => $order_number, 
            'web_number' => $web_number, 
            'status_id' => $status_id, 
            'user_id' => $user_id, 
            'delivery_date' => $delivery_date,
            'created_at' => $created_at);

        $json = json_encode($array);

        return response()->json($json);
    }

    /**
     * Return the specified resource through ajax.
     *
     * @param  int  $id
     * @return Response
     */
    public function ajaxItem($item_number) {

        $item_number = $item_number;

        $user = \Auth::user();

        $item = Items::leftJoin('items_to_franchises','items.id', '=', 'items_to_franchises.item_id')
        ->select(['items.item_number', 'items.item_name', 'items_to_franchises.pop_up', 'items_to_franchises.stock', 'items_to_franchises.committed', 'items.item_price'])
        ->where('items_to_franchises.franchise_id', '=', $user->franchise_id)
        ->where('items.item_number', '=', $item_number)->get();

        $json = json_encode($item);

        return response()->json($json);
    }

    /**
     * Return the specified resource through ajax.
     *
     * @param  int  $id
     * @return Response
     */
    public function ajaxOrderModal($order_number) {

        $order_number = $order_number;

        $order = \DB::table('orders')->where('order_number', $order_number)->first();
        $customer = \DB::table('customers')->where('id', $order->customer_id)->first();
        $activity = \DB::table('activities')->where('customer_id', $customer->id)->first();

        if(is_null($customer) || is_null($order) || is_null($activity)) {

            $array = array(
            'id' => "", 
            'customer_id' => "", 
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
            'id' => $order->id, 
            'customer_id' => $customer->id, 
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
     * Return all Sales Orders data through ajax.
     *
     * @param  int  $id
     * @return Response
     */
    public function orderData() {

        $user = \Auth::user();

        if($user->access_level >= 2) {

            $orders = Order::leftJoin('customers','orders.customer_id', '=', 'customers.id')
            ->leftJoin('users' , 'orders.user_id', '=', 'users.id')
            ->leftJoin('franchises' , 'orders.franchise_id', '=', 'franchises.id')
            ->leftJoin('order_status' , 'orders.status_id', '=', 'order_status.id')
            ->select(['orders.id', 'order_number', 'customers.customer_number AS customer_number', 'customers.surname AS surname', 'total', 'delivery_date', 'orders.created_at AS created_at', 'users.username AS user', 'franchises.franchise_name AS franchise', 'order_status.name AS status'])
            ->orderBy('orders.created_at', 'desc')
            ->get();

        }
        else {

            $orders = Order::leftJoin('customers','orders.customer_id', '=', 'customers.id')
            ->leftJoin('users' , 'orders.user_id', '=', 'users.id')
            ->leftJoin('franchises' , 'orders.franchise_id', '=', 'franchises.id')
            ->leftJoin('order_status' , 'orders.status_id', '=', 'order_status.id')
            ->select(['orders.id', 'order_number', 'customers.customer_number AS customer_number', 'customers.surname AS surname', 'total', 'delivery_date', 'orders.created_at AS created_at', 'users.username AS user', 'franchises.franchise_name AS franchise', 'order_status.name AS status'])
            ->where('orders.franchise_id', '=', $user->franchise_id)
            ->orderBy('orders.created_at', 'desc')
            ->get();
        }

        return Datatables::of($orders)
            ->addColumn('action', function ($order) {
                //return '<a href="../customer/'.$user->id.'" class="btn btn-xs btn-primary"><i class="glyphicon glyphicon-edit"></i> Edit</a>';
                return '<div class="btn-group"><button type="button" class="open-orderModal btn btn-default btn-xs btn-flat" data-id="' . $order->order_number .'" data-toggle="modal" data-target="#orderModal"><i class="fa fa-arrow-right"></i></button></div>';
            })->make(true); 
    }
}
