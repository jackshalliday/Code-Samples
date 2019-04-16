<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use View;

use App\Activity;
use App\Customers;
use App\Franchise;
use App\Items;
use App\Order;
use App\Round;
use App\User;

class DashboardController extends Controller
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

        $no_franchises = Franchise::count();
        $no_rounds = Round::count();
        $no_items = Items::count();
        $no_users = User::count();

        $no_sales_orders = Order::whereRaw('date(created_at) = ?', [\Carbon\Carbon::today()])->count();
        $no_sales_orders_user = Order::whereRaw('date(created_at) = ?', [\Carbon\Carbon::today()])->where('franchise_id', $user->franchise_id)->count();

        $no_calls = Activity::whereRaw('date(next_activity_date) = ?', [\Carbon\Carbon::today()])->count();
        $no_calls_user = Activity::leftJoin('customers', 'activities.customer_id', 'customers.id')
        ->whereRaw('date(next_activity_date) = ?', [\Carbon\Carbon::today()])->where('franchise_id', $user->franchise_id)->count();

        $no_customers = Customers::whereRaw('date(created_at) = ?', [\Carbon\Carbon::today()])->count();
        $no_customers_user = Customers::whereRaw('date(created_at) = ?', [\Carbon\Carbon::today()])->where('franchise_id', $user->franchise_id)->count();

        $added_products = Items::orderBy('created_at', 'desc')->limit(4)->get();

        if($user->access_level >= 2) {

            $latest_sales_orders = Order::orderBy('order_number', 'desc')->limit(6)->get();
        }
        else {

            $latest_sales_orders = Order::orderBy('order_number', 'desc')
            ->where('franchise_id', $user->franchise_id)
            ->limit(6)->get();
        }

        $revenue_thirty_days = Order::whereRaw('date(created_at) <= ?', [\Carbon\Carbon::today()])
        ->whereRaw('date(created_at) >= ?', [\Carbon\Carbon::now()->subDays(30)])
        ->sum('total');

        $sales_thirty_days = Order::whereRaw('date(created_at) <= ?', [\Carbon\Carbon::today()])
        ->whereRaw('date(created_at) >= ?', [\Carbon\Carbon::now()->subDays(30)])
        ->count();

        $customers_thirty_days = Customers::whereRaw('date(created_at) <= ?', [\Carbon\Carbon::today()])
        ->whereRaw('date(created_at) >= ?', [\Carbon\Carbon::now()->subDays(30)])
        ->count();

        $franchise_performance = Order::leftJoin('franchises', 'orders.franchise_id', 'franchises.id')
        ->whereRaw('date(orders.created_at) <= ?', [\Carbon\Carbon::today()])
        ->whereRaw('date(orders.created_at) >= ?', [\Carbon\Carbon::now()->subDays(30)])
        ->groupBy('franchises.id')
        ->selectRaw('count(orders.id) as count, franchises.franchise_name AS franchise')
        ->limit(5)
        ->get();

        $orders_chart_data = Order::whereRaw('date(orders.created_at) <= ?', [\Carbon\Carbon::today()])
        ->whereRaw('date(orders.created_at) >= ?', [\Carbon\Carbon::now()->subDays(30)])
        ->select(array(\DB::Raw('count(orders.id) as count'), \DB::Raw('DATE(orders.created_at) as day')))
        ->groupBy('day')
        ->get();

        return View::make('dashboard.index', compact('user', 'no_franchises', 'no_rounds', 
            'no_items', 'no_users', 'no_sales_orders', 'no_sales_orders_user', 'no_calls', 'no_calls_user',
            'no_customers', 'no_customers_user', 'added_products', 'latest_sales_orders', 'revenue_thirty_days',
            'sales_thirty_days', 'customers_thirty_days', 'franchise_performance', 'orders_chart_data'))
        ->with('dates', $orders_chart_data->pluck('day'))
        ->with('totals', $orders_chart_data->pluck('count'));
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
}
