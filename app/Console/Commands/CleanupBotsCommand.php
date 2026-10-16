<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\UserCleanupService;
use Illuminate\Console\Command;

class CleanupBotsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bots:cleanup {--pattern=%@example.% : Email pattern to match} {--force : Force deletion without interactive confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete bot/seeder users and all their associated posts, topics, comments, and interactions';

    /**
     * Execute the console command.
     */
    public function handle(UserCleanupService $cleanupService): int
    {
        $pattern = $this->option('pattern');
        $force = $this->option('force');

        $botUsers = User::where('email', 'like', $pattern)
            ->where('role', '!=', 'admin')
            ->get(['id', 'username', 'email', 'role']);

        if ($botUsers->isEmpty()) {
            $this->info("No bot users found matching pattern '{$pattern}'.");
            return 0;
        }

        $this->warn("Found {$botUsers->count()} bot user(s) matching pattern '{$pattern}':");
        $this->table(['ID', 'Username', 'Email', 'Role'], $botUsers->map(fn($u) => [$u->id, $u->username, $u->email, $u->role]));

        if (!$force && !$this->confirm('Do you really want to permanently delete these users and ALL their content (posts, forums, comments, etc.)?')) {
            $this->info('Operation cancelled.');
            return 0;
        }

        $this->info('Cleaning up bot users and cascading records...');
        $stats = $cleanupService->purgeUsers($botUsers->pluck('id')->all());

        $this->info('Cleanup completed successfully!');
        $this->table(
            ['Entity', 'Count Deleted'],
            collect($stats)->map(fn($count, $entity) => [$entity, $count])->values()->all()
        );

        return 0;
    }
}
