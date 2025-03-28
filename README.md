
# Docker Setup for Laravel Queue Processing Challenge

This branch (`dockerize-app`) focuses solely on adding Docker configuration to the project to enable easy setup and execution of the application.

## Docker Configuration

The application has been containerized with Docker using the following services:
- **app**: PHP application container
- **nginx**: Web server
- **db**: MySQL database
- **redis**: Redis for queue processing

## Running the Application with Docker

### Prerequisites

- Docker
- Docker Compose

### Setup Instructions

1. Clone the repository and switch to the dockerize-app branch:
   ```bash
   git clone https://github.com/yourusername/queue-processing-challenge.git
   cd queue-processing-challenge
   ```

2. Create the environment file:
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

3. Start the Docker containers:
   ```bash
   docker compose up -d
   ```

4. Install dependencies:
   ```bash
   docker compose exec app composer install
   ```

5. Generate application key:
   ```bash
   docker compose exec app php artisan key:generate
   ```

6. Run migrations:
   ```bash
   docker compose exec app php artisan migrate
   ```

7. Access the application:
   ```
   http://localhost:8082
   ```

### Running Queue Worker

To process jobs from the queue:

```bash
docker compose exec app php artisan queue:work
```

### Stopping Containers

When you're done, you can stop the containers:

```bash
docker compose down
```

## Docker Configuration Files

- `docker-compose.yml`: Defines services, networks, and volumes
- `Dockerfile`: PHP application configuration
- `docker/nginx/conf.d/app.conf`: Nginx web server configuration
- `docker/php/local.ini`: PHP configuration
- `docker/mysql/my.cnf`: MySQL configuration

## Troubleshooting Docker Setup

If you encounter issues:

1. Check container status:
   ```bash
   docker compose ps
   ```

2. View container logs:
   ```bash
   docker compose logs app
   docker compose logs nginx
   docker compose logs db
   ```

3. Ensure Nginx configuration is correctly mounted:
   ```bash
   docker compose exec nginx ls -la /etc/nginx/conf.d/
   ```

4. Verify database connection:
   ```bash
   docker compose exec app php artisan tinker --execute="DB::connection()->getPdo();"
   ```
