<?php

namespace App\Services\Cleanup\Models;

use Carbon\Carbon;

class CheckpointRecord
{
    public string $id;
    public string $commit_hash;
    public string $operation;
    public array $metadata;
    public Carbon $created_at;
    public string $session_id;

    public function __construct(array $data)
    {
        $this->id = $data['id'];
        $this->commit_hash = $data['commit_hash'];
        $this->operation = $data['operation'];
        $this->metadata = $data['metadata'] ?? [];
        $this->created_at = $data['created_at'] instanceof Carbon ? $data['created_at'] : Carbon::parse($data['created_at']);
        $this->session_id = $data['session_id'];
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'commit_hash' => $this->commit_hash,
            'operation' => $this->operation,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at->toISOString(),
            'session_id' => $this->session_id,
        ];
    }
}