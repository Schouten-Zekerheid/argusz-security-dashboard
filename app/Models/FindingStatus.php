<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;

/**
 * @property string $service_id
 * @property string $pipeline_run_id
 * @property string $scan_source
 * @property string $fingerprint
 * @property string $current_status
 * @property string|array $tool
 * @property string $severity
 * @property string $type
 * @property string $title
 * @property string|null $reference_id
 * @property string|null $updated_by
 * @property string|null $resolution_reason
 * @property string|null $snooze_reason
 * @property string|null $jira_issue_key
 * @property mixed $status_updated_at
 * @property mixed $history
 * @property Carbon|string|null $created_at
 * @property Carbon|string|null $updated_at
 */
class FindingStatus extends Model
{
    use HasFactory;

    protected $connection = 'mongodb';

    protected $collection = 'finding_statuses';

    protected $fillable = [
        'service_id',
        'pipeline_run_id',
        'scan_source',
        'fingerprint',
        'current_status',
        'tool',
        'severity',
        'type',
        'title',
        'reference_id',
        'status_updated_at',
        'updated_by',
        'resolution_reason',
        'snooze_reason',
        'jira_issue_key',
        'history',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function pipelineRun()
    {
        return $this->belongsTo(PipelineRun::class, 'pipeline_run_id');
    }
}
