<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use MongoDB\Laravel\Schema\Blueprint;

return new class extends Migration
{
    /**
     * Check if MongoDB connection is configured.
     */
    private function mongodbIsConfigured(): bool
    {
        $config = config('database.connections.mongodb', []);

        return ! empty($config['dsn']) || ! empty($config['host']);
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! $this->mongodbIsConfigured()) {
            return;
        }

        Schema::connection('mongodb')->table('finding_statuses', function (Blueprint $collection) {
            // Index voor de upsert (cruciaal voor performance ingest)
            $collection->index(['service_id', 'fingerprint']);

            // Index voor dashboard filters en status-overzichten
            $collection->index(['service_id', 'current_status']);

            // Index voor severity-checks
            $collection->index('severity');
        });

        Schema::connection('mongodb')->table('pipeline_runs', function (Blueprint $collection) {
            $collection->index('service_id');
            $collection->index('meta.commit_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! $this->mongodbIsConfigured()) {
            return;
        }

        Schema::connection('mongodb')->table('finding_statuses', function (Blueprint $collection) {
            $collection->dropIndex(['service_id', 'fingerprint']);
            $collection->dropIndex(['service_id', 'current_status']);
            $collection->dropIndex('severity');
        });

        Schema::connection('mongodb')->table('pipeline_runs', function (Blueprint $collection) {
            $collection->dropIndex('service_id');
            $collection->dropIndex('meta.commit_hash');
        });
    }
};
