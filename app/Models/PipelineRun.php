<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;

/**
 * @property string $service_id
 * @property string $ingestion_hash
 * @property string $schema_version
 * @property mixed $meta
 * @property mixed $runs
 * @property mixed $ingested_at
 * @property bool|null $is_cleaned
 */
class PipelineRun extends Model
{
    use HasFactory;

    protected $connection = 'mongodb';

    protected $collection = 'pipeline_runs';

    protected $fillable = [
        'service_id',
        'ingestion_hash',
        'schema_version',
        'meta',
        'runs',
        'ingested_at',
        'is_cleaned',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function findingStatuses()
    {
        return $this->hasMany(FindingStatus::class, 'pipeline_run_id');
    }
}
