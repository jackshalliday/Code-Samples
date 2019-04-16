<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use View;

use App\Customers;
use App\Activity;

use App\Http\Requests\ActivityFormRequest;

use Yajra\Datatables\Facades\Datatables;

class ActivityController extends Controller
{
    /**
     * Instantiate a new ActivityController instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('user_active');
    }

    //show all activities data
    public function activityData() {

        $user = \Auth::user();

        $customers = Customers::leftJoin('franchises','customers.franchise_id', '=', 'franchises.id')
            ->leftJoin('customer_categories' , 'customers.category_id', '=', 'customer_categories.id')
            ->leftJoin('activities', 'customers.id', '=', 'activities.customer_id')
            ->select(['customers.customer_number AS customer_number', 'customers.surname AS surname', 'customer_categories.name AS category', 'activities.next_activity_date AS next_activity_date', 'activities.updated_at AS updated_at', 'customers.user_id AS user', 'franchises.franchise_name AS franchise'])->where('customers.franchise_id', '=', $user->franchise_id)->get();

        return Datatables::of($customers)
            ->addColumn('action', function ($customer) {
                //return '<a href="../customer/'.$user->id.'" class="btn btn-xs btn-primary"><i class="glyphicon glyphicon-edit"></i> Edit</a>';
                return '<div class="btn-group"><button type="button" class="open-activityModal btn btn-default btn-xs btn-flat" data-id="' . $customer->customer_number .'" data-toggle="modal" data-target="#activityModal"><i class="fa fa-arrow-right"></i></button></div>';

            })->make(true); 
    }

     /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $user = \Auth::user();
        return View::make('activity.index', compact('user'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        \App::abort(404);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(ActivityFormRequest $request)
    {
        \App::abort(404);
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
        $activity = Activity::find($id);
        $customer = $activity->customer()->first();

        if($customer->is_closed) {

            return View::make('activity.show', compact('customer', 'activity', 'user'));
        }

        return \Redirect::route('activity.edit', array($id));
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
        $todays_date = \Carbon\Carbon::now()->format('d/m/Y');

        $activity = Activity::find($id);
        $customer = $activity->customer()->first();

        if($customer->is_closed) {

            return \Redirect::route('activity.show', array($id));
        }

        $activity->next_activity_date = date('d/m/Y', strtotime($activity->next_activity_date));
        
        return View::make('activity.edit', compact('customer', 'activity', 'todays_date', 'user'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function update($id, ActivityFormRequest $request)
    {
        $activity = Activity::find($id);

        $activity->next_activity_date = \DateTime::createFromFormat('d/m/Y', $request->next_activity_date);
        $activity->title = $request->title;
        $activity->notes = $request->notes;
        $activity->updated_at  = \Carbon\Carbon::now();

        $activity->save();

        // redirect
        \Session::flash('success_message', 'Successfully updated the Activity!');
        return \Redirect::route('activity.edit', array($id));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        \App::abort(404);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function withCustomer($id) {

        $customer = Customers::find($id);

        $activity_id = $customer->activity()->first()->id;

        return \Redirect::route('activity.edit', array($activity_id));
    }
}
