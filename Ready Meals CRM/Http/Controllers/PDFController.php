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

use PDF;

class PDFController extends Controller
{
	/**
     * Instantiate a new PDFController instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('user_active');
    }
    
    /**
     * Generate PDF from sales order.
     *
     * @param  int  $id
     * @return Response
     */
    public function salesOrder($id) {

    	$sales_order = Order::find($id);
        $sales_order->delivery_date = date('d/m/Y', strtotime($sales_order->delivery_date));

    	$pdf = PDF::loadView('pdf.sales_order', compact('sales_order'));
    	return $pdf->stream('sales_order.pdf');
    }
}
