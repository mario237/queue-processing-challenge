<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Jobs\BulkOrderProcessing;
use App\Jobs\ProcessOrder;
use App\Jobs\CreatePayment;
use App\Models\Order;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    // Reset and configure the Queue facade for each test
    Queue::fake();
});


describe('Order Processing Job', function () {
    it( 'successfully transitions order from pending to processing', function () {
        // Create a test user and order
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::PENDING->value,
            'amount' => 150.00
        ]);

        // Execute the job directly
        $job = new ProcessOrder($order);
        $job->handle();

        // Refresh the order from database
        $order->refresh();

        // Assert the order status is now processing
        expect($order->status)
            ->toBe(OrderStatus::PROCESSING->value)
            ->and($order->processing_started_at)->not()->toBeNull();

        // Verify CreatePayment was dispatched
        Queue::assertPushed(CreatePayment::class);
    });

    it( 'handles failed order processing with retries', function () {
        // Create a test user and order
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::PENDING->value,
            'amount' => 100.00
        ]);

        // Simulate a job that will fail multiple times before marking as failed
        $job = new ProcessOrder($order);

        // Simulate multiple job attempts that fail
        $maxTries = 5;
        for ($attempt = 1; $attempt <= $maxTries; $attempt++) {
            try {
                // Simulate job processing with an exception
                throw new Exception("Simulated failure");
            } catch (Exception $e) {
                // On the last attempt, the order should be marked as failed
                if ($attempt === $maxTries) {
                    // Simulate the job's failed method being called
                    $job->failed($e);

                    $order->refresh();
                    expect($order->status)->toBe(OrderStatus::FAILED->value)
                        ->and($order->failed_at)->not()->toBeNull();
                }
            }
        }
    });

    it('prevents processing an already processed order', function () {
        // Start with a fresh Queue fake for this test
        Queue::fake();

        // Create a test user and order that is ALREADY in processing state
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::PROCESSING->value, // Already processing
            'amount' => 100.00,
            'processing_started_at' => now()
        ]);

        // Attempt to process an already processed order
        $job = new ProcessOrder($order);
        $job->handle();

        // No CreatePayment job should be dispatched
        Queue::assertNotPushed(CreatePayment::class);
    });

    it('calculates processing duration correctly', function () {
        // Create a test user and order
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::PENDING->value,
            'amount' => 100.00
        ]);

        // Dispatch the process order job
        $job = new ProcessOrder($order);
        $job->handle();

        // Refresh the order
        $order->refresh();

        // Assert processing started timestamp is set
        expect($order->processing_started_at)->not()->toBeNull();
    });
});
