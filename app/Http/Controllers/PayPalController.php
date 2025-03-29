<?php
namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\PayPalPaymentService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PayPalController extends Controller
{
    protected PayPalPaymentService $paypalService;

    public function __construct(PayPalPaymentService $paypalService)
    {
        $this->paypalService = $paypalService;
    }

    public function handleSuccess(Request $request, Order $order)
    {
        try {
            // Verify PayPal order
            $verified = $this->paypalService->verifyPayment($request->input('token'));

            if ($verified) {
                // Capture the payment
                $captureResult = $this->paypalService->capturePayment($request->input('token'));

                if ($captureResult['success']) {
                    // Update order status
                    $order->update([
                        'status' => 'completed',
                        'payment_status' => 'paid'
                    ]);

                    return redirect()->route('payment.complete')
                        ->with('success', 'Payment completed successfully');
                }
            }

            // Payment failed
            $order->update([
                'payment_status' => OrderStatus::FAILED->value,
                'status' =>  OrderStatus::FAILED->value
            ]);

            return redirect()->route('payment.failed')
                ->with('error', 'Payment verification failed');
        } catch (Exception $e) {
            Log::error('Payment Handling Error', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('payment.failed')
                ->with('error', 'An unexpected error occurred');
        }
    }

    public function handleCancel(Order $order)
    {
        $order->update([
            'status' => 'cancelled',
            'payment_status' => 'cancelled'
        ]);

        return redirect()->route('payment.cancelled')
            ->with('message', 'Payment was cancelled');
    }
}
