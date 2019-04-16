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
use App\Http\Requests\ReportFormRequest;
use App\PaymentTerms;
use App\PrimarySources;
use App\Round;
use App\Timeslot;
use App\User;
use App\OrderStatus;
use App\Order;
use App\Items;
use App\ItemsToFranchise;

use PDF;

class ReportController extends Controller
{
    /**
     * Instantiate a new ReportController instance.
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
        if($user->access_level >= 2) {

            $rounds = Round::leftJoin('franchises','rounds.franchise_id', '=', 'franchises.id')
            ->select(['rounds.id AS round_id', \DB::raw('CONCAT(rounds.round_name, " (", franchises.franchise_name, ")") AS round_name')])
            ->orderBy('franchises.id', 'asc')
            ->get();

            $rounds = $rounds->pluck('round_name', 'round_id')->prepend('ALL ROUNDS (' . $user->franchise()->first()->franchise_name . ')','0');
        }
        else {
            $rounds = Round::where('franchise_id', $user->franchise_id)->pluck('round_name', 'id')->prepend('ALL ROUNDS','0');
        }
        return View::make('report.index', compact('user', 'rounds'));
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
    public function store(ReportFormRequest $request)
    {
        $user = \Auth::user();
        if($user->access_level >= 2) {

            $rounds = Round::leftJoin('franchises','rounds.franchise_id', '=', 'franchises.id')
            ->select(['rounds.id AS round_id', \DB::raw('CONCAT(rounds.round_name, " (", franchises.franchise_name, ")") AS round_name')])
            ->orderBy('franchises.id', 'asc')
            ->get();

            $rounds = $rounds->pluck('round_name', 'round_id')->prepend('ALL ROUNDS (' . $user->franchise()->first()->franchise_name . ')','0');
        }
        else {
            $rounds = Round::where('franchise_id', $user->franchise_id)->pluck('round_name', 'id')->prepend('ALL ROUNDS','0');
        }

        if(!is_numeric($request->from_order) || !is_numeric($request->to_order) ) {

            \Session::flash('error_message', 'Item numbers must be numeric! Please try again.');
            return View::make('report.index', compact('user', 'rounds'));
        }

        $from_date = \DateTime::createFromFormat('d/m/Y', $request->from_date)->format('Y-m-d');
        $to_date = \DateTime::createFromFormat('d/m/Y', $request->to_date)->format('Y-m-d');

        if($to_date < $from_date) {

            \Session::flash('error_message', 'To date cannot be before from date. Please try again.');
            return View::make('report.index', compact('user', 'rounds'));
        }

        if($request->round_id == 0) {
            $orders = Order::where('delivery_date', '>=', $from_date)
            ->where('delivery_date', '<=', $to_date)
            ->where('order_number', '>=', $request->from_order)
            ->where('order_number', '<=', $request->to_order)
            ->where('franchise_id', '=', $user->franchise_id)
            ->get();

        }
        else {
            $orders = Order::where('delivery_date', '>=', $from_date)
            ->where('delivery_date', '<=', $to_date)
            ->where('round_id', '=', $request->round_id)
            ->where('order_number', '>=', $request->from_order)
            ->where('order_number', '<=', $request->to_order)
            ->where('franchise_id', '=', $user->franchise_id)
            ->get();
        }

        if($orders->isEmpty()) {
            \Session::flash('error_message', 'No records found. Please try again.');
            return View::make('report.index', compact('user', 'rounds'));
        }

        $pdf = PDF::loadView('pdf.sales_order_multiple', compact('user', 'orders'));
        return $pdf->stream('sales_order.pdf');
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
}
