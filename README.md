# Laravel Queue Processing Challenge

This project implements a robust order processing queue system with Docker configuration for easy setup and execution. The application demonstrates advanced queue processing techniques, including job retries, error handling, and integration with payment gateways.

## System Overview

The application processes orders through a series of queued jobs:

1. **Bulk Order Processing**: Dispatches multiple order processing jobs
2. **Order Processing**: Transitions orders from pending to processing state
3. **Payment Creation**: Integrates with PayPal for payment processing
4. **Payment Completion**: Finalizes order status based on payment results

## Docker Configuration

The application has been containerized with Docker using the following services:
- **app**: PHP application container with Laravel
- **nginx**: Web server
- **db**: MySQL database
- **redis**: Redis for queue processing and caching
- **horizon**: Laravel Horizon for queue monitoring

## Setup Instructions

### Prerequisites

- Docker
- Docker Compose

### 1. Clone the Repository

```bash
git clone https://github.com/yourusername/queue-processing-challenge.git
cd queue-processing-challenge
```

### 2. Create Environment File

```bash
cp .env.example .env
```

Ensure the following environment variables are set correctly in your `.env` file:
```
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=queue_processing_challenge
DB_USERNAME=laravel
DB_PASSWORD=secret

REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

QUEUE_CONNECTION=redis
```

### 3. Start Docker Containers

```bash
docker-compose up -d
```

### 4. Install Dependencies

```bash
docker-compose exec app composer install
```

### 5. Generate Application Key

```bash
docker-compose exec app php artisan key:generate
```

### 6. Run Migrations

```bash
docker-compose exec app php artisan migrate
```

### 7. Run Queue

```bash
docker-compose exec app php artisan queue:work
```

### 8. Access the Application

The application is accessible at:
```
http://localhost:8082/dashboard
```

Horizon dashboard (for queue monitoring):
```
http://localhost:8082/horizon
```

## Usage

### Processing Orders

The system has two main endpoints to trigger order processing:

1. **Process Pending Orders**
   ```
   Go to http://localhost:8082/dashboard
   Press "Process Pending Orders" Button
   ```

2. **Retry Failed Orders**
   ```
   Go to http://localhost:8082/dashboard
   Press "Retry Failed Orders" Button
   ```

### Queue Structure

The system uses three separate queues:
- `orders` - For individual order processing
- `paypal` - For payment processing
- `bulk-orders` - For handling bulk order operations

Queue workers are managed by Supervisor and configured in the Docker setup.

## Testing

Run the test suite using:

```bash
docker-compose exec app php artisan test
```



## Key Features

1. **Robust Error Handling**:
    - Automatic retries with exponential backoff
    - Comprehensive logging for debugging
    - Failed job handling through Horizon

2. **Performance Optimization**:
    - Multiple queue workers for different job types
    - Supervisor configuration for process management
    - Efficient database operations with transactions

3. **Payment Gateway Integration**:
    - PayPal payment processing
    - Status tracking and verification
    - Error handling for payment failures

## Troubleshooting

### Queue Issues

If queue jobs are not processing:

1. Check Redis connection:
   ```bash
   docker-compose exec redis redis-cli ping
   ```

2. View Supervisor logs:
   ```bash
   docker-compose exec app cat /tmp/laravel-worker.log
   ```

3. Check Laravel logs:
   ```bash
   docker-compose exec app tail -f storage/logs/laravel.log
   ```

### Database Issues

If you encounter database connection problems:

1. Verify the database is running:
   ```bash
   docker-compose ps db
   ```

2. Check database connection:
   ```bash
   docker-compose exec app php artisan tinker --execute="DB::connection()->getPdo();"
   ```

## Stopping Containers

When you're done, you can stop the containers:

```bash
docker-compose down
```
