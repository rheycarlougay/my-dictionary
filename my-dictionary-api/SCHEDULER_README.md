# Laravel Scheduler for Favorites Cleanup

This document explains how to set up and use the Laravel scheduler to automatically clean up old favorites that were created more than 30 days ago.

## 🕐 **Schedule Configuration**

The scheduler is configured to run **every day at 12:00 AM** and will automatically clean up favorites that were created more than 30 days ago.

### **Schedule Details:**
- **Frequency**: Daily at 00:00 (12:00 AM)
- **Command**: `favorites:cleanup`
- **Log File**: `storage/logs/favorites-cleanup.log`
- **Overlap Protection**: Enabled (prevents multiple instances running simultaneously)

## 🛠 **Setup Instructions**

### **1. Verify Command Registration**

The cleanup command is automatically registered in `app/Console/Kernel.php`. Make sure the file exists and contains:

```php
protected function schedule(Schedule $schedule): void
{
    $schedule->command('favorites:cleanup')
        ->dailyAt('00:00')
        ->withoutOverlapping()
        ->runInBackground()
        ->appendOutputTo(storage_path('logs/favorites-cleanup.log'));
}
```

### **2. Set Up Cron Job**

To make the scheduler work, you need to add a cron job to your server:

```bash
# Edit your crontab
crontab -e

# Add this line (replace with your actual project path)
* * * * * cd /path/to/your/my-dictionary-api && php artisan schedule:run >> /dev/null 2>&1
```

**For Windows (Task Scheduler):**
1. Open Task Scheduler
2. Create a new task that runs every minute
3. Command: `php artisan schedule:run`
4. Working directory: Your Laravel project path

### **3. Test the Setup**

Run these commands to test your setup:

```bash
# Check if the command is registered
php artisan list | grep favorites

# Test the command with dry-run
php artisan favorites:cleanup --dry-run

# Check scheduled tasks
php artisan schedule:list
```

## 📋 **Command Options**

The `favorites:cleanup` command supports several options:

### **Basic Usage:**
```bash
# Run with default settings (30 days, with confirmation)
php artisan favorites:cleanup

# Run with custom days
php artisan favorites:cleanup --days=60

# Dry run (show what would be deleted without actually deleting)
php artisan favorites:cleanup --dry-run

# Force run (skip confirmation prompts)
php artisan favorites:cleanup --force

# Send notifications to users (logs notification events)
php artisan favorites:cleanup --notify
```

### **Combined Options:**
```bash
# Dry run with custom days
php artisan favorites:cleanup --days=45 --dry-run

# Force cleanup with notifications
php artisan favorites:cleanup --force --notify

# Custom days with dry run and notifications
php artisan favorites:cleanup --days=90 --dry-run --notify
```

## 🔍 **What the Command Does**

### **1. Finds Old Favorites**
- Searches for favorites where `created_at` is older than the specified days (default: 30)
- Groups favorites by user for better reporting
- Shows summary of affected users

### **2. User Notifications (Optional)**
- When `--notify` is used, logs notification events for each affected user
- In a production environment, you could extend this to send actual emails/SMS

### **3. Soft Deletion**
- Uses Laravel's soft delete functionality
- Sets `deleted_at` timestamp instead of permanently removing records
- Users can still restore favorites from trash if needed

### **4. Comprehensive Logging**
- Logs each deleted favorite with details
- Logs summary statistics
- Logs any errors that occur during cleanup
- Output is also saved to `storage/logs/favorites-cleanup.log`

## 📊 **Monitoring and Logs**

### **Log Files:**
- **Laravel Log**: `storage/logs/laravel.log`
- **Cleanup Log**: `storage/logs/favorites-cleanup.log`

### **Sample Log Entries:**
```log
[2024-01-15 00:00:01] local.INFO: Cleaned up old favorite {"favorite_id":123,"word":"example","user_id":1,"created_at":"2023-12-15 10:30:00","deleted_at":"2024-01-15 00:00:01"}
[2024-01-15 00:00:02] local.INFO: Favorites cleanup completed {"total_checked":5,"deleted_count":3,"affected_users":2,"cutoff_date":"2023-12-16 00:00:00","notifications_sent":false}
```

### **Monitoring Commands:**
```bash
# Check recent cleanup logs
tail -f storage/logs/favorites-cleanup.log

# Check Laravel logs for cleanup events
grep "favorites cleanup" storage/logs/laravel.log

# Count total favorites
php artisan tinker --execute="echo 'Total favorites: ' . App\Models\Favorite::count();"

# Count soft-deleted favorites
php artisan tinker --execute="echo 'Trashed favorites: ' . App\Models\Favorite::onlyTrashed()->count();"
```

## 🧪 **Testing**

### **1. Run the Test Script:**
```bash
php test_scheduler.php
```

This script will:
- Create test users if none exist
- Create test favorites with different ages
- Show what would be cleaned up
- Provide setup instructions

### **2. Manual Testing:**
```bash
# Create a test favorite that's 35 days old
php artisan tinker --execute="
\$user = App\Models\User::first();
if (\$user) {
    \$favorite = App\Models\Favorite::create([
        'word' => 'test_old',
        'note' => 'Test old favorite',
        'user_id' => \$user->id,
        'created_at' => now()->subDays(35),
        'updated_at' => now()
    ]);
    echo 'Created test favorite: ' . \$favorite->word;
}
"

# Test the cleanup
php artisan favorites:cleanup --dry-run
```

## ⚙️ **Customization**

### **Change Default Days:**
Edit the command signature in `app/Console/Commands/CleanupOldFavorites.php`:
```php
protected $signature = 'favorites:cleanup {--days=60 : Number of days to consider a favorite as old}';
```

### **Change Schedule Time:**
Edit `app/Console/Kernel.php`:
```php
// Run every 6 hours
$schedule->command('favorites:cleanup')->everyFourHours();

// Run weekly on Monday at 2 AM
$schedule->command('favorites:cleanup')->weekly()->mondays()->at('02:00');

// Run monthly on the 1st at 3 AM
$schedule->command('favorites:cleanup')->monthlyOn(1, '03:00');
```

### **Add Email Notifications:**
Extend the `sendNotifications` method in the command to send actual emails:
```php
// In the sendNotifications method
Mail::to($user->email)->send(new OldFavoritesNotification($favorites, $days));
```

## 🚨 **Troubleshooting**

### **Common Issues:**

1. **Scheduler Not Running:**
   - Check if cron job is properly set up
   - Verify the project path in crontab
   - Check Laravel logs for errors

2. **Command Not Found:**
   - Clear Laravel cache: `php artisan cache:clear`
   - Clear config cache: `php artisan config:clear`
   - Verify command is registered: `php artisan list`

3. **Permission Issues:**
   - Ensure Laravel has write permissions to `storage/logs/`
   - Check file permissions for log files

4. **Database Errors:**
   - Verify database connection
   - Check if `favorites` table exists
   - Ensure `created_at` column exists

### **Debug Commands:**
```bash
# Check if scheduler is working
php artisan schedule:run --verbose

# Test command manually
php artisan favorites:cleanup --dry-run --days=1

# Check database connection
php artisan tinker --execute="echo 'DB connected: ' . (DB::connection()->getPdo() ? 'Yes' : 'No');"
```

## 📈 **Performance Considerations**

- The command uses database transactions for safety
- Soft deletion preserves data integrity
- Logging is comprehensive but not excessive
- The command runs in background to avoid blocking other processes
- Overlap protection prevents multiple instances

## 🔒 **Security**

- Only authenticated users' favorites are processed
- Soft deletion allows data recovery
- Comprehensive logging for audit trails
- No sensitive data is logged (passwords, tokens, etc.)
