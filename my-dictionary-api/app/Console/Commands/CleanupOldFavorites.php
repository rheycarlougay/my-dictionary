<?php

namespace App\Console\Commands;

use App\Models\Favorite;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CleanupOldFavorites extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'favorites:cleanup 
                            {--days=30 : Number of days to consider a favorite as old} 
                            {--dry-run : Show what would be deleted without actually deleting}
                            {--notify : Send notifications to users before cleanup}
                            {--force : Skip confirmation prompts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up favorites that were created more than the specified number of days ago';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');
        $dryRun = $this->option('dry-run');
        $notify = $this->option('notify');
        $force = $this->option('force');
        
        // Calculate the cutoff date (30 days ago from now)
        $cutoffDate = Carbon::now()->subDays($days);
        
        $this->info("Checking for favorites created before: {$cutoffDate->format('Y-m-d H:i:s')}");
        
        // Get favorites that were created more than the specified number of days ago
        $oldFavorites = Favorite::with('user')
            ->where('created_at', '<', $cutoffDate)
            ->orderBy('created_at', 'asc')
            ->get();
        
        $count = $oldFavorites->count();
        
        if ($count === 0) {
            $this->info('No old favorites found to clean up.');
            return 0;
        }
        
        $this->info("Found {$count} favorites that were created more than {$days} days ago.");
        
        // Group favorites by user for better reporting
        $favoritesByUser = $oldFavorites->groupBy('user_id');
        $affectedUsers = $favoritesByUser->count();
        
        $this->info("This will affect {$affectedUsers} users.");
        
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No favorites will be deleted');
            $this->displayFavoritesTable($oldFavorites);
            return 0;
        }
        
        // Show summary before proceeding
        $this->displaySummary($favoritesByUser);
        
        // Confirm deletion (unless --force is used)
        if (!$force && !$this->confirm("Are you sure you want to delete {$count} old favorites?")) {
            $this->info('Operation cancelled.');
            return 0;
        }
        
        // Send notifications if requested
        if ($notify) {
            $this->sendNotifications($favoritesByUser, $days);
        }
        
        // Soft delete the old favorites
        $deletedCount = $this->performCleanup($oldFavorites);
        
        $this->info("Successfully cleaned up {$deletedCount} old favorites.");
        
        // Log summary
        Log::info("Favorites cleanup completed", [
            'total_checked' => $count,
            'deleted_count' => $deletedCount,
            'affected_users' => $affectedUsers,
            'cutoff_date' => $cutoffDate->format('Y-m-d H:i:s'),
            'notifications_sent' => $notify
        ]);
        
        return 0;
    }
    
    /**
     * Display favorites in a table format
     */
    private function displayFavoritesTable($favorites)
    {
        $this->table(
            ['ID', 'Word', 'User', 'User ID', 'Created At', 'Updated At'],
            $favorites->map(function ($favorite) {
                return [
                    $favorite->id,
                    $favorite->word,
                    $favorite->user ? $favorite->user->name : 'Unknown',
                    $favorite->user_id,
                    $favorite->created_at->format('Y-m-d H:i:s'),
                    $favorite->updated_at->format('Y-m-d H:i:s')
                ];
            })->toArray()
        );
    }
    
    /**
     * Display summary of affected users and their favorites
     */
    private function displaySummary($favoritesByUser)
    {
        $this->info("\nSummary of affected users:");
        
        foreach ($favoritesByUser as $userId => $favorites) {
            $user = $favorites->first()->user;
            $userName = $user ? $user->name : "User ID: {$userId}";
            $this->line("- {$userName}: {$favorites->count()} favorites");
        }
    }
    
    /**
     * Send notifications to users about their old favorites
     */
    private function sendNotifications($favoritesByUser, $days)
    {
        $this->info("Sending notifications to users...");
        
        foreach ($favoritesByUser as $userId => $favorites) {
            $user = $favorites->first()->user;
            if (!$user) {
                $this->warn("User ID {$userId} not found, skipping notification");
                continue;
            }
            
            $favoriteCount = $favorites->count();
            $oldestFavorite = $favorites->sortBy('created_at')->first();
            $daysOld = $oldestFavorite->created_at->diffInDays(now());
            
            // Log the notification (in a real app, you might send email/SMS)
            Log::info("Notification sent to user about old favorites", [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'favorites_count' => $favoriteCount,
                'oldest_favorite_days' => $daysOld,
                'message' => "You have {$favoriteCount} favorites that were created more than {$days} days ago"
            ]);
            
            $this->line("Notified {$user->name} ({$user->email}) about {$favoriteCount} old favorites");
        }
    }
    
    /**
     * Perform the actual cleanup of old favorites
     */
    private function performCleanup($favorites)
    {
        $deletedCount = 0;
        $errors = [];
        
        DB::beginTransaction();
        
        try {
            foreach ($favorites as $favorite) {
                try {
                    $favorite->delete(); // This will soft delete (set deleted_at)
                    $deletedCount++;
                    
                    // Log the deletion
                    Log::info("Cleaned up old favorite", [
                        'favorite_id' => $favorite->id,
                        'word' => $favorite->word,
                        'user_id' => $favorite->user_id,
                        'created_at' => $favorite->created_at,
                        'deleted_at' => now()
                    ]);
                    
                } catch (\Exception $e) {
                    $errors[] = "Failed to delete favorite ID {$favorite->id}: {$e->getMessage()}";
                    Log::error("Failed to clean up old favorite", [
                        'favorite_id' => $favorite->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Database transaction failed: {$e->getMessage()}");
            Log::error("Favorites cleanup transaction failed", [
                'error' => $e->getMessage()
            ]);
            return 0;
        }
        
        // Display any errors
        if (!empty($errors)) {
            $this->warn("Some favorites could not be deleted:");
            foreach ($errors as $error) {
                $this->error($error);
            }
        }
        
        return $deletedCount;
    }
}
