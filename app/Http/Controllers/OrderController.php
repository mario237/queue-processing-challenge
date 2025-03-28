<?php

namespace App\Http\Controllers;

use App\Http\Responses\ApiResponse;
use App\Jobs\BulkOrderProcessing;
use App\Models\Order;
use Exception;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function processPendingOrders()
    {
        try {
            // Fetch all pending orders
            $pendingOrders = Order::pending()->oldest()->get();

            if ($pendingOrders->isEmpty()) {
                return ApiResponse::error('No pending orders to process');
            }
            // Dispatch processing job for all pending order
            BulkOrderProcessing::dispatch($pendingOrders);

            return ApiResponse::success([
                'total_pending_orders' => $pendingOrders->count()
            ], 'Pending orders processing started');
        }catch (Exception $e) {
            Log::error("Error while Bulk processing pending order  " . $e->getMessage());
            return ApiResponse::error('Failed to start processing pending orders');
        }
    }

    public function retryFailedOrders()
    {
        try {
            // Fetch all pending orders
            $failedOrders = Order::failed()->oldest()->get();

            if ($failedOrders->isEmpty()) {
                return ApiResponse::error('No failed orders to process');
            }
            // Dispatch processing job for all pending order
            BulkOrderProcessing::dispatch($failedOrders);

            return ApiResponse::success([
                'total_failed_orders' => $failedOrders->count()
            ], 'Failed orders processing started');
        }catch (Exception $e) {
            Log::error("Error while Bulk processing failed orders  " . $e->getMessage());
            return ApiResponse::error('Failed to start processing failed orders');
        }
    }

}
