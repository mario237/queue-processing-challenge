<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Processing Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .status-pending { background-color: #f8f9fa; }
        .status-processing { background-color: #cfe2ff; }
        .status-completed { background-color: #d1e7dd; }
        .status-failed { background-color: #f8d7da; }
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
                        @foreach(['pending', 'processing', 'completed', 'failed'] as $status)
                            <tr class="status-{{ $status }}">
                                <td>{{ ucfirst($status) }}</td>
                                <td>{{ $orderCounts[$status] }}</td>
                                <td>
                                    {{ $stats['total'] > 0 ? round(($orderCounts[$status] / $stats['total']) * 100, 1) : 0 }}%
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
                                <span class="badge bg-{{
                                    $order->status === 'pending' ? 'secondary' :
                                    ($order->status === 'processing' ? 'primary' :
                                    ($order->status === 'completed' ? 'success' : 'danger'))
                                }}">
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
                <a href="#" class="btn btn-primary">Create New Order</a>
                <a href="#" class="btn btn-secondary">View All Orders</a>
                <a href="#" class="btn btn-warning">Process Pending Orders</a>
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
            labels: ['Pending', 'Processing', 'Completed', 'Failed'],
            datasets: [{
                data: [
                    {{ $orderCounts['pending'] }},
                    {{ $orderCounts['processing'] }},
                    {{ $orderCounts['completed'] }},
                    {{ $orderCounts['failed'] }}
                ],
                backgroundColor: [
                    '#f8f9fa',
                    '#cfe2ff',
                    '#d1e7dd',
                    '#f8d7da'
                ],
                borderColor: [
                    '#dee2e6',
                    '#9ec5fe',
                    '#a3cfbb',
                    '#f1aeb5'
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
</script>
</body>
</html>
