<?php

require_once 'vendor/autoload.php';

use App\Models\User;
use App\Models\Favorite;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Scheduler Test Script ===\n\n";

// Check if users exist
$userCount = User::count();
echo "Total users in database: {$userCount}\n";

if ($userCount === 0) {
    echo "No users found. Creating test users...\n";
    
    $user1 = User::create([
        'name' => 'Test User 1',
        'email' => 'test1@example.com',
        'password' => Hash::make('password123'),
    ]);
    
    $user2 = User::create([
        'name' => 'Test User 2',
        'email' => 'test2@example.com',
        'password' => Hash::make('password123'),
    ]);
    
    echo "Created test users:\n";
    echo "- User 1: {$user1->name} ({$user1->email})\n";
    echo "- User 2: {$user2->name} ({$user2->email})\n\n";
} else {
    $users = User::all();
    echo "Existing users:\n";
    foreach ($users as $user) {
        echo "- {$user->name} ({$user->email})\n";
    }
    echo "\n";
}

// Check favorites table
echo "=== Favorites Table Check ===\n";
try {
    $favorites = Favorite::all();
    echo "Total favorites in database: " . $favorites->count() . "\n";
    
    if ($favorites->count() > 0) {
        echo "Sample favorites:\n";
        foreach ($favorites->take(5) as $favorite) {
            $user = $favorite->user;
            $userName = $user ? $user->name : "Unknown";
            echo "- ID: {$favorite->id}, Word: {$favorite->word}, User: {$userName}, Updated: {$favorite->updated_at}\n";
        }
    }
} catch (Exception $e) {
    echo "Error checking favorites: " . $e->getMessage() . "\n";
}

// Create some test favorites with different dates
echo "\n=== Creating Test Favorites ===\n";

$users = User::all();
if ($users->count() > 0) {
    $user = $users->first();
    
    // Create a recent favorite (should not be cleaned up)
    $recentFavorite = Favorite::create([
        'word' => 'recent',
        'note' => 'This is a recent favorite',
        'user_id' => $user->id,
        'created_at' => now()->subDays(5), // 5 days ago
        'updated_at' => now()
    ]);
    
    // Create an old favorite (should be cleaned up)
    $oldFavorite = Favorite::create([
        'word' => 'old',
        'note' => 'This is an old favorite',
        'user_id' => $user->id,
        'created_at' => now()->subDays(35), // 35 days ago
        'updated_at' => now()
    ]);
    
    // Create another old favorite
    $veryOldFavorite = Favorite::create([
        'word' => 'very_old',
        'note' => 'This is a very old favorite',
        'user_id' => $user->id,
        'created_at' => now()->subDays(45), // 45 days ago
        'updated_at' => now()
    ]);
    
    echo "Created test favorites:\n";
    echo "- Recent favorite: 'recent' (created 5 days ago)\n";
    echo "- Old favorite: 'old' (created 35 days ago)\n";
    echo "- Very old favorite: 'very_old' (created 45 days ago)\n\n";
}

// Test the cleanup command
echo "=== Testing Cleanup Command ===\n";
echo "Running dry-run to see what would be cleaned up:\n";

// Simulate running the command
$cutoffDate = Carbon::now()->subDays(30);
$oldFavorites = Favorite::where('created_at', '<', $cutoffDate)->get();

echo "Cutoff date: {$cutoffDate->format('Y-m-d H:i:s')}\n";
echo "Favorites created more than 30 days ago: {$oldFavorites->count()}\n";

if ($oldFavorites->count() > 0) {
    echo "These favorites would be cleaned up:\n";
    foreach ($oldFavorites as $favorite) {
        $user = $favorite->user;
        $userName = $user ? $user->name : "Unknown";
        $daysOld = $favorite->created_at->diffInDays(now());
        echo "- '{$favorite->word}' by {$userName} (created {$daysOld} days ago)\n";
    }
} else {
    echo "No favorites would be cleaned up.\n";
}

echo "\n=== Scheduler Setup Instructions ===\n";
echo "1. The scheduler is configured to run daily at 12:00 AM\n";
echo "2. To test the command manually, run:\n";
echo "   php artisan favorites:cleanup --dry-run\n";
echo "3. To run the actual cleanup:\n";
echo "   php artisan favorites:cleanup --force\n";
echo "4. To set up the cron job (add to your server's crontab):\n";
echo "   * * * * * cd /path/to/your/project && php artisan schedule:run >> /dev/null 2>&1\n";
echo "5. To check scheduled tasks:\n";
echo "   php artisan schedule:list\n";

echo "\n=== Test Complete ===\n";
