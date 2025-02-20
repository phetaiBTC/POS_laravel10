<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OrderPayment;


class OrderPaymentController extends Controller
{
    public function updateStatus($Pid)
    {
        return response()->json(['message' => 'Payment status updated successfully']);
        // try {
        //     $payment = OrderPayment::find($Pid);
        //     $payment->status = 'success';
        //     $payment->save();
        //     return response()->json(['message' => 'Payment status updated successfully']);
        // } catch (Exception $e) {
        //     return response()->json(['error' => 'Failed to update payment status', 'message' => $e->getMessage()], 500);
        // }
    }
}
