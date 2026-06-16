<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;

/**
 * @property string $name
 * @property string $repository_url
 * @property string|null $image_ref
 * @property bool $active
 * @property string|null $default_branch
 */
class Service extends Model
{
    use HasFactory;

    protected $connection = 'mongodb';

    protected $collection = 'services';

    protected $fillable = [
        'name',
        'repository_url',
        'active',
        'default_branch',
    ];

    public function pipelineRuns()
    {
        return $this->hasMany(PipelineRun::class, 'service_id');
    }

    public function findingStatuses()
    {
        return $this->hasMany(FindingStatus::class, 'service_id');
    }
}
