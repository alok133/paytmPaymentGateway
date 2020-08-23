<?php


namespace App\Http\Controllers;


use PaytmWallet;
use Illuminate\Http\Request;
use App\EventRegistration;


class OrderController extends Controller
{


    /**
     * Redirect the user to the Payment Gateway.
     *
     * @return Response
     */
    public function register()
    {
        return view('payment_form');
    }


    /**
     * Redirect the user to the Payment Gateway.
     *
     * @return Response
     */
    public function order(Request $request)
    {


        


        $input = $request->all();
        $input['order_id'] = $request->mobile_no.rand(1,100);
        $input['fee'] = 50;


        EventRegistration::create($input);


        $payment = PaytmWallet::with('receive');
        $payment->prepare([
          'order' => $input['order_id'],
          'user' => $request->name,
          'mobile_number' => $request->mobile_no,
          'email' => $request->email,
          'amount' => $input['fee'],
          'callback_url' => url('api/payment/status')
        ]);
        return $payment->receive();
    }


    /**
     * Obtain the payment information.
     *
     * @return Object
     */
    public function paymentCallback()
    {
        $transaction = PaytmWallet::with('receive');


        $response = $transaction->response();
        $order_id = $transaction->getOrderId();


        if($transaction->isSuccessful()){
          EventRegistration::where('order_id',$order_id)->update(['status'=>2, 'transaction_id'=>$transaction->getTransactionId()]);


          dd('Payment Successfully Paid.');
        }else if($transaction->isFailed()){
          EventRegistration::where('order_id',$order_id)->update(['status'=>1, 'transaction_id'=>$transaction->getTransactionId()]);
          dd('Payment Failed.');
        }
    }    
}
