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
use App\Http\Requests\ItemFormRequest;
use App\PaymentTerms;
use App\PrimarySources;
use App\Round;
use App\Timeslot;
use App\User;
use App\OrderStatus;
use App\Order;
use App\Items;
use App\ItemsToFranchise;



class ItemsController extends Controller
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
    	$item = new Items;
        return View::make('item.index', compact('item', 'user'));
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
    public function store(ItemFormRequest $request)
    {
    	$user = \Auth::user();
    	$item = new Items;

    	if(\Input::get('update')) {

    		if(!is_numeric($request->stock)) {

	        	\Session::flash('error_message', 'Stock must be a number! Please try again.');
	        	return View::make('item.index', compact('item', 'user'));
	        }

	        if(!is_numeric($request->committed)) {

	        	\Session::flash('error_message', 'Committed must be a number! Please try again.');
	        	return View::make('item.index', compact('item', 'user'));
	        }

    		$item = Items::find($request->item_id);
    	}

        $item_exists = \DB::table('items')->where('item_number', $request->item_number)->first();

        $item->item_number = $request->item_number;
        $item->item_price = $request->item_price;
        $item->item_name = $request->item_name;
        $item->item_description = $request->item_description;

        if(!is_null($item_exists)) {

        	if($item->id != $item_exists->id) {

	        	\Session::flash('error_message', 'Item Number already exists! Please try again.');
	        	return View::make('item.index', compact('item', 'user'));
	        }
        }
     
        if(!is_numeric($request->item_number)) {

        	\Session::flash('error_message', 'Item Number must be a number! Please try again.');
        	return View::make('item.index', compact('item', 'user'));
        }

        if(!is_numeric($request->item_price)) {

        	\Session::flash('error_message', 'Item Price must be a number! Please try again.');
        	return View::make('item.index', compact('item', 'user'));
        }

        if(is_null($request->item_description)) {

        	$item->item_description = "";
        }

        if($user->access_level >= 2) {

	        $item->save();
	    }

	    if(\Input::get('update')) {

	    	$items_to_franchise  = \DB::table('items_to_franchises')
	    	->where('item_id', $item->id)
	    	->where('franchise_id', $user->franchise_id)
	    	->first();

	    	$items_to_franchise = ItemsToFranchise::find($items_to_franchise->id);

        	$items_to_franchise->pop_up = $request->pop_up;
        	$items_to_franchise->stock = $request->stock;
        	$items_to_franchise->committed = $request->committed;

        	$items_to_franchise->save();

        	\Session::flash('success_message', 'Product ' . $item->item_number . ' successfully updated!');
    	}
    	else {

    		$franchises = \DB::table('franchises')->get();

	        foreach($franchises AS $franchise) {

	        	$items_to_franchise = new ItemsToFranchise;

	        	$items_to_franchise->item_id = $item->id;
	        	$items_to_franchise->franchise_id = $franchise->id;
	        	$items_to_franchise->pop_up = "";
	        	$items_to_franchise->stock = 0;
	        	$items_to_franchise->committed = 0;

	        	$items_to_franchise->save();
	        }

	        \Session::flash('success_message', 'Product ' . $item->item_number . ' successfully created!');
    	}

	    $item = new Items;
	    return View::make('item.index', compact('item', 'user'));
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
    public function update($id, ItemFormRequest $request)
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
    public function ajaxItemsModal($item_number) {

        $item_number = $item_number;
        $user = \Auth::user();
        $item = \DB::table('items')->where('item_number', $item_number)->first();

        $items_to_franchise = \DB::table('items_to_franchises')
        ->where('item_id', $item->id)
        ->where('franchise_id', $user->franchise_id)->first();

        if(is_null($item) || is_null($items_to_franchise)) {

            $array = array(
            'item_id' => "", 
            'item_name' => "", 
            'item_price' => "", 
            'item_description' => "", 
            'pop_up' => "", 
            'stock' => "", 
            'committed' => "");

            $json = json_encode($array);
            return response()->json($json);
        }
        else {

            $array = array(
            'item_id' => $item->id, 
            'item_name' => $item->item_name, 
            'item_price' => $item->item_price, 
            'item_description' => $item->item_description, 
            'pop_up' => $items_to_franchise->pop_up, 
            'stock' => $items_to_franchise->stock, 
            'committed' => $items_to_franchise->committed);

            $json = json_encode($array);
            return response()->json($json);
        } 
    }

     /**
     * Return all items data through ajax.
     *
     * @param  int  $id
     * @return Response
     */
    public function itemsData() {

        $user = \Auth::user();

        if($user->access_level >= 2) {

            $items = ItemsToFranchise::leftJoin('items','items_to_franchises.item_id', '=', 'items.id')
            ->leftJoin('franchises' , 'items_to_franchises.franchise_id', '=', 'franchises.id')
            ->select(['items.item_number AS item_number', 'items.item_name AS item_name', 'items_to_franchises.pop_up AS pop_up', 'items_to_franchises.stock AS stock', 'items_to_franchises.committed AS committed', 'items.item_price AS item_price', 'franchises.franchise_name AS franchise', 'franchises.id AS franchise_id'])
            ->orderBy('franchises.franchise_name', 'asc')
            ->orderBy('items.item_number', 'asc')
            ->get();
        }
        else {

            $items = ItemsToFranchise::leftJoin('items','items_to_franchises.item_id', '=', 'items.id')
            ->leftJoin('franchises' , 'items_to_franchises.franchise_id', '=', 'franchises.id')
            ->select(['items.item_number AS item_number', 'items.item_name AS item_name', 'items_to_franchises.pop_up AS pop_up', 'items_to_franchises.stock AS stock', 'items_to_franchises.committed AS committed', 'items.item_price AS item_price', 'franchises.franchise_name AS franchise', 'franchises.id AS franchise_id'])
            ->where('items_to_franchises.franchise_id', '=', $user->franchise_id)
            ->orderBy('franchises.franchise_name', 'asc')
            ->orderBy('items.item_number', 'asc')
            ->get();

        }

        return Datatables::of($items)
        ->addColumn('action', function ($item) {

            $user = \Auth::user();

            if($item->franchise_id != $user->franchise_id) {
                return '<div class="btn-group"><button type="button" class="open-itemsModal btn btn-default btn-xs btn-flat" data-id="' . $item->item_number .'" data-toggle="modal" data-target="#itemsModal" disabled><i class="fa fa-arrow-right"></i></button></div>';
            }
                //return '<a href="../customer/'.$user->id.'" class="btn btn-xs btn-primary"><i class="glyphicon glyphicon-edit"></i> Edit</a>';
            return '<div class="btn-group"><button type="button" class="open-itemsModal btn btn-default btn-xs btn-flat" data-id="' . $item->item_number .'" data-toggle="modal" data-target="#itemsModal"><i class="fa fa-arrow-right"></i></button></div>';

        })->make(true); 
    }
}
