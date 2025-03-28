<?php
namespace App\Services;

use App\Models\Order;
use Srmklive\PayPal\Services\PayPal as PayPalClient;
use Illuminate\Support\Facades\Log;
use Exception;
use Throwable;

class PayPalPaymentService
{
    protected PayPalClient $paypalClient;

    /**
     * @throws Throwable
     */
    public function __construct()
    {
        $this->paypalClient = new PayPalClient;
         $this->paypalClient->setApiCredentials(config('paypal'));
        $paypalToken =  $this->paypalClient->getAccessToken();
        $this->paypalClient->setAccessToken($paypalToken);
    }


    /**
     * Create a PayPal payment for an order
     *
     * @param Order $order
     * @return array
     * @throws Exception
     * @throws Throwable
     */
    public function createPayment(Order $order): array
    {
        Log::info('Creating PayPal payment', [
            'client' => $this->paypalClient,
            'order' => $order->id
        ]);
        try {
            // Prepare payment data
            $data = [
                'intent' => 'CAPTURE',
                'purchase_units' => [
                    [
                        'amount' => [
                            'currency_code' => config('paypal.currency', 'USD'),
                            'value' => number_format($order->amount, 2, '.', '')
                        ],
                        'description' => "Order #$order->id Payment"
                    ]
                ],
                'application_context' => [
                    'brand_name' => config('app.name'),
                    'locale' => config('app.locale', 'en_US'),
                    'landing_page' => 'BILLING',
                    'shipping_preference' => 'NO_SHIPPING',
                    'user_action' => 'PAY_NOW',
                    'return_url' => route('payment.success', ['order' => $order->id]),
                    'cancel_url' => route('payment.cancel', ['order' => $order->id])
                ]
            ];

            // Create order with PayPal
            $paypalOrder = $this->paypalClient->createOrder($data);

            // Validate PayPal order response
            if (!isset($paypalOrder['id'])) {
                throw new Exception('Failed to create PayPal order');
            }


            // Log successful order creation
            Log::info('PayPal Order Created', [
                'order_id' => $order->id,
                'paypal_order_id' => $paypalOrder['id'],
                'status' => $paypalOrder['status'] ?? 'unknown'
            ]);

            return [
                'success' => true,
                'paypal_order_id' => $paypalOrder['id'],
            ];
        } catch (Exception $e) {
            // Log detailed error
            Log::error('PayPal Payment Creation Failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

        }
        return [
            'success' => false,
            'error' => 'Failed to create PayPal payment'
        ];
    }

    /**
     * Capture a PayPal payment
     *
     * @param string $paypalOrderId
     * @return array
     * @throws Exception|Throwable
     */
    public function capturePayment(string $paypalOrderId): array
    {
        try {
            // Capture the PayPal order
            $capture = $this->paypalClient->capturePaymentOrder($paypalOrderId);

            // Validate capture response
            if (!isset($capture['status'])) {
                throw new Exception('Invalid payment capture response');
            }

            // Log payment capture
            Log::info('PayPal Payment Captured', [
                'paypal_order_id' => $paypalOrderId,
                'status' => $capture['status']
            ]);

            return [
                'success' => $capture['status'] === 'COMPLETED',
                'details' => $capture
            ];
        } catch (Exception $e) {
            // Log capture failure
            Log::error('PayPal Payment Capture Failed', [
                'paypal_order_id' => $paypalOrderId,
                'error' => $e->getMessage()
            ]);
        }
        return [
            'success' => false,
            'error' => 'Failed to capture PayPal payment'
        ];
    }
}
