<?php

require_once 'vendor/autoload.php';

use App\Models\User;
use Illuminate\Support\Facades\Hash;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Authentication Test Script ===\n\n";

// Check if users exist
$userCount = User::count();
echo "Total users in database: {$userCount}\n";

if ($userCount === 0) {
    echo "No users found. Creating a test user...\n";
    
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => Hash::make('password123'),
    ]);
    
    echo "Created test user:\n";
    echo "- ID: {$user->id}\n";
    echo "- Name: {$user->name}\n";
    echo "- Email: {$user->email}\n";
    echo "- Password: password123\n\n";
} else {
    $users = User::all();
    echo "Existing users:\n";
    foreach ($users as $user) {
        echo "- ID: {$user->id}, Name: {$user->name}, Email: {$user->email}\n";
    }
    echo "\n";
}

// Check favorites table
echo "=== Favorites Table Check ===\n";
try {
    $favorites = \App\Models\Favorite::all();
    echo "Total favorites in database: " . $favorites->count() . "\n";
    
    if ($favorites->count() > 0) {
        echo "Sample favorites:\n";
        foreach ($favorites->take(3) as $favorite) {
            echo "- ID: {$favorite->id}, Word: {$favorite->word}, User ID: {$favorite->user_id}\n";
        }
    }
} catch (Exception $e) {
    echo "Error checking favorites: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
echo "You can now test the authentication with:\n";
echo "1. Start Laravel server: php artisan serve\n";
echo "2. Start Vue frontend: npm run dev\n";
echo "3. Login with test@example.com / password123\n";
