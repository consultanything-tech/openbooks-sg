<?php

namespace App\View\Components;

use App\Models\ActivityLog;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

/**
 * Per-record activity timeline sourced from the existing activity_logs table.
 * Renders nothing when the record has no logged activity yet.
 */
class ActivityTimeline extends Component
{
    public Collection $activities;

    public bool $hasMore;

    public function __construct(
        public string $modelType,
        public int|string $modelId,
        public string $title = 'Activity',
        public int $limit = 8,
    ) {
        $this->activities = ActivityLog::with('user')
            ->where('model_type', $this->modelType)
            ->where('model_id', $this->modelId)
            ->recent()
            ->limit($this->limit + 1)
            ->get();

        $this->hasMore = $this->activities->count() > $this->limit;
        $this->activities = $this->activities->take($this->limit);
    }

    public function render()
    {
        return view('components.activity-timeline');
    }
}
