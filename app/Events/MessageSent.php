<?php

namespace App\Events;

use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public ChatMessage $message;

    protected array $participants = [];

    public function __construct(ChatMessage $message)
    {
        $this->message = $message->loadMissing(['sender', 'conversation.participants']);
        $this->participants = $this->message->conversation->participants->map(function (User $participant) {
            return [
                'id' => $participant->id,
                'name' => $participant->name,
            ];
        })->toArray();
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('conversation.' . $this->message->conversation_id);
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->message->conversation_id,
            'participants' => $this->participants,
            'message' => [
                'id' => $this->message->id,
                'body' => $this->message->body,
                'sender_id' => $this->message->sender_id,
                'sender_name' => $this->message->sender?->name,
                'created_at' => $this->message->created_at?->toDateTimeString(),
            ],
        ];
    }

    public function broadcastAs(): string
    {
        return 'MessageSent';
    }
}
