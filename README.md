# jwt-auth-task
jwt-auth-task for high tech software training 
# jwt-auth-task
JWT based authentication for high tech software training task 
# JWT-Based Authentication Task (Laravel)

## Tech Stack
- PHP, Laravel 10+
- MySQL
- JWT Authentication (`tymon/jwt-auth`)
- dotenv for environment variables

## Folder Structure
- `/app/Http/Controllers` – Controllers
- `/app/Models` – Models
- `/app/Http/Middleware` – Middleware
- `/routes` – `api.php` (for API routes)
- `.env` – Environment variables

## Environment Variables Routes for api
```env
APP_NAME=Laravel
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=jwt_login
DB_USERNAME=root
DB_PASSWORD=root123

JWT_SECRET=7iIbtmKdmXCe6Xd11uOBLqV0bEY5BqI1JLG5xQ7XwrM6E3at9z7a3pDY1vUY4CBd
access token valid for 15 minutes 
and refresh token is valid for the 
30 days
## Routes for api
following are the routes for the api's 
Route::post('/register', [JwtController::class, 'register']);
Route::post('/login', [JwtController::class, 'login']);
Route::post('/logout', [JwtController::class, 'logout']);
Route::post('/refresh-token', [AuthController::class, 'refreshToken']);

Route::middleware(['jwt'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'index']);
});
