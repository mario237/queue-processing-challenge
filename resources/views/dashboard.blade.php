<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Processing Dashboard</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .status-pending {
            background-color: #6c757d;
            color: #f0f0f0;
        }
        .status-processing {
            background-color: #0d6efd;
            color: #e6f2ff;
        }
        .status-completed {
            background-color: #198754;
            color: #e6f5ea;
        }
        .status-failed {
            background-color: #dc3545;
            color: #fbe9ec;
        }
    </style>
</head>
<body>
<div class="container my-5">
    <h1 class="mb-4">Order Processing Dashboard</h1>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Total Orders</h5>
                    <h2 class="card-text">{{ $stats['total'] }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Total Amount</h5>
                    <h2 class="card-text">${{ number_format($stats['total_amount'], 2) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Average Order</h5>
                    <h2 class="card-text">${{ number_format($stats['avg_amount'], 2) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Success Rate</h5>
                    <h2 class="card-text">{{ $stats['success_rate'] }}%</h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Counts and Chart -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Order Status</div>
                <div class="card-body">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Status Breakdown</div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                        <tr>
                            <th>Status</th>
                            <th>Count</th>
                            <th>Percentage</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($statuses as $status)
                            <tr class="status-{{ $status->value }}">
                                <td>{{ $status->label() }}</td>
                                <td>{{ $orderCounts[$status->value] }}</td>
                                <td>
                                    {{ $stats['total'] > 0 ? round(($orderCounts[$status->value] / $stats['total']) * 100, 1) : 0 }}%
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Orders -->
    <div class="card mb-4">
        <div class="card-header">Recent Orders</div>
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Updated</th>
                </tr>
                </thead>
                <tbody>
                @forelse($recentOrders as $order)
                    <tr>
                        <td>{{ $order->id }}</td>
                        <td>{{ $order->user->name }}</td>
                        <td>${{ number_format($order->amount, 2) }}</td>
                        <td>
                                <span class="status-{{ $order->status }}">
                                    {{ ucfirst($order->status) }}
                                </span>
                        </td>
                        <td>{{ $order->created_at->format('Y-m-d H:i:s') }}</td>
                        <td>{{ $order->updated_at->format('Y-m-d H:i:s') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center">No orders found</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Actions -->
    <div class="card">
        <div class="card-header">Actions</div>
        <div class="card-body">
            <div class="d-flex gap-2">
                <button id="processPendingBtn" class="btn btn-warning">Process Pending Orders</button>
                <button id="retryFailedBtn" class="btn btn-danger">Retry Failed Orders</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Initialize chart
    const ctx = document.getElementById('statusChart').getContext('2d');
    const statusChart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: ['pending', 'processing', 'completed', 'failed'],
            datasets: [{
                data: [
                    {{ $orderCounts['pending'] }},
                    {{ $orderCounts['processing'] }},
                    {{ $orderCounts['completed'] }},
                    {{ $orderCounts['failed'] }}
                ],
                backgroundColor: [
                    '#6c757d',  // Pending
                    '#0d6efd',  // Processing
                    '#198754',  // Completed
                    '#dc3545'   // Failed
                ],
                borderColor: [
                    '#dee2e6',  // Pending
                    '#9ec5fe',  // Processing
                    '#a3cfbb',  // Completed
                    '#f1aeb5'   // Failed
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                }
            }
        }
    });

    // Process Pending Orders with SweetAlert confirmation
    document.getElementById('processPendingBtn').addEventListener('click', function(e) {
        // Show confirmation dialog
        Swal.fire({
            title: 'Process Pending Orders',
            text: 'Are you sure you want to process all pending orders?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, Process Orders!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // Setup CSRF token
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });

                // Show loading state
                Swal.fire({
                    title: 'Processing Orders',
                    text: 'Please wait...',
                    didOpen: () => {
                        Swal.showLoading();

                        // Ajax request to process orders
                        $.ajax({
                            url: '{{ route("orders.process-pending") }}',
                            method: 'GET',
                            timeout: 30000, // 30 second timeout
                            dataType: 'json', // Explicitly set expected data type
                            complete: function(xhr, status) {
                                // Log complete details for debugging
                                console.log('Response:', xhr.responseText);
                            },
                            success: function(response) {
                                // Success message
                                Swal.fire({
                                    title: 'Success!',
                                    html: `
                                <p>Order processing initiated successfully.</p>
                                <p>Total Pending Orders: ${response.data.total_pending_orders}</p>
                            `,
                                    icon: 'success',
                                    confirmButtonText: 'OK'
                                });
                            },
                            error: function(xhr, status, error) {
                                // Comprehensive error handling
                                let errorMessage = 'An unexpected error occurred';

                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    errorMessage = xhr.responseJSON.message;
                                } else if (xhr.statusText) {
                                    errorMessage = xhr.statusText;
                                }

                                // Error message
                                Swal.fire({
                                    title: 'Processing Failed',
                                    html: `
                                <p>Failed to process orders.</p>
                                <p>${errorMessage}</p>
                            `,
                                    icon: 'error',
                                    confirmButtonText: 'Okay'
                                });
                            }
                        });
                    }
                });
            }
        });
    });

    // Retry Failed Orders with SweetAlert confirmation

    document.getElementById('retryFailedBtn').addEventListener('click', function(e) {
        // Show confirmation dialog
        Swal.fire({
            title: 'Process Failed Orders',
            text: 'Are you sure you want to process all failed orders?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, Process Orders!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // Setup CSRF token
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });

                // Show loading state
                Swal.fire({
                    title: 'Processing Orders',
                    text: 'Please wait...',
                    didOpen: () => {
                        Swal.showLoading();

                        // Ajax request to process orders
                        $.ajax({
                            url: '{{ route("orders.process-failed") }}',
                            method: 'GET',
                            timeout: 30000, // 30 second timeout
                            dataType: 'json', // Explicitly set expected data type
                            complete: function(xhr, status) {
                                // Log complete details for debugging
                                console.log('Response:', xhr.responseText);
                            },
                            success: function(response) {
                                // Success message
                                Swal.fire({
                                    title: 'Success!',
                                    html: `
                                <p>Order processing initiated successfully.</p>
                                <p>Total Failed Orders: ${response.data.total_failed_orders}</p>
                            `,
                                    icon: 'success',
                                    confirmButtonText: 'OK'
                                });
                            },
                            error: function(xhr, status, error) {
                                // Comprehensive error handling
                                let errorMessage = 'An unexpected error occurred';

                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    errorMessage = xhr.responseJSON.message;
                                } else if (xhr.statusText) {
                                    errorMessage = xhr.statusText;
                                }

                                // Error message
                                Swal.fire({
                                    title: 'Processing Failed',
                                    html: `
                                <p>Failed to process orders.</p>
                                <p>${errorMessage}</p>
                            `,
                                    icon: 'error',
                                    confirmButtonText: 'Okay'
                                });
                            }
                        });
                    }
                });
            }
        });
    });

</script>
</body>
</html>
