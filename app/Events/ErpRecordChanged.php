<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ErpRecordChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $recordType,
        public int $recordId,
        public string $status
    ) {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('erp.live')];
    }

    public function broadcastAs(): string
    {
        return 'record.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'record_type' => $this->recordType,
            'id' => $this->recordId,
            'status' => $this->status,
        ];
    }
}
