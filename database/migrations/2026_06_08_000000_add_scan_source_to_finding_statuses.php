<?php

use App\Models\FindingStatus;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private function mongodbIsConfigured(): bool
    {
        $config = config('database.connections.mongodb', []);

        return ! empty($config['dsn']) || ! empty($config['host']);
    }

    public function up(): void
    {
        if (! $this->mongodbIsConfigured()) {
            return;
        }

        FindingStatus::whereNull('scan_source')->update([
            'scan_source' => 'github',
        ]);
    }

    public function down(): void
    {
        if (! $this->mongodbIsConfigured()) {
            return;
        }

        FindingStatus::where('scan_source', 'github')->update([
            'scan_source' => null,
        ]);
    }
};
