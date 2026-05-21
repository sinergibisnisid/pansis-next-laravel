<?php

namespace App\DTOs\Notification;

use Illuminate\Http\Request;

readonly class SendNotificationDTO
{
    public function __construct(
        public ?string $userId = null,
        public ?string $branchId = null,
        public string $channel = 'email',
        public string $type = 'info',
        public string $title = '',
        public string $body = '',
        public ?string $recipient = null,
        public ?array $metadata = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            userId: $request->input('user_id'),
            branchId: $request->input('branch_id'),
            channel: $request->input('channel', 'email'),
            type: $request->input('type', 'info'),
            title: $request->input('title'),
            body: $request->input('body'),
            recipient: $request->input('recipient'),
            metadata: $request->input('metadata'),
        );
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'branch_id' => $this->branchId,
            'channel' => $this->channel,
            'type' => $this->type,
            'title' => $this->title,
            'body' => $this->body,
            'recipient' => $this->recipient,
            'metadata' => $this->metadata,
        ];
    }
}
