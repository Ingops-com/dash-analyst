<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

class Conversation extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_user')
            ->withPivot('last_read_at')
            ->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(ChatMessage::class)->latestOfMany();
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->whereHas('participants', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        });
    }

    public function hasParticipant(int $userId): bool
    {
        if ($this->relationLoaded('participants')) {
            return $this->participants->contains('id', $userId);
        }

        return $this->participants()->where('user_id', $userId)->exists();
    }

    public function lastReadAtFor(int $userId): ?Carbon
    {
        if (! $this->relationLoaded('participants')) {
            $this->loadMissing('participants');
        }

        $participant = $this->participants->first(fn (User $user) => $user->id === $userId);

        if (! $participant) {
            return null;
        }

        return $this->normalizeCarbon($participant->pivot->last_read_at);
    }

    public function markAsRead(int $userId): void
    {
        $this->participants()->updateExistingPivot($userId, [
            'last_read_at' => now(),
        ]);
    }

    public function unreadCountFor(int $userId): int
    {
        $query = $this->messages()->where('sender_id', '!=', $userId);

        if ($lastRead = $this->lastReadAtFor($userId)) {
            $query->where('created_at', '>', $lastRead);
        }

        return $query->count();
    }

    public static function betweenUsers(array $userIds): ?self
    {
        $userIds = array_values(array_unique($userIds));

        if (count($userIds) < 2) {
            return null;
        }

        $query = static::query();

        foreach ($userIds as $id) {
            $query->whereHas('participants', function ($sub) use ($id) {
                $sub->where('user_id', $id);
            });
        }

        $conversation = $query->first();

        if (! $conversation) {
            return null;
        }

        $conversation->loadMissing('participants');

        if ($conversation->participants->count() !== count($userIds)) {
            return null;
        }

        return $conversation;
    }

    public static function getOrCreateForUsers(array $userIds): self
    {
        $userIds = array_values(array_unique($userIds));
        sort($userIds);

        $conversation = static::betweenUsers($userIds);

        if ($conversation) {
            return $conversation;
        }

        $conversation = static::create();
        $participants = [];

        foreach ($userIds as $id) {
            $participants[$id] = ['last_read_at' => null];
        }

        $conversation->participants()->attach($participants);

        return $conversation;
    }

    public function seenByRecipients(ChatMessage $message): bool
    {
        if (! $this->relationLoaded('participants')) {
            $this->loadMissing('participants');
        }

        $sentAt = $message->created_at;

        foreach ($this->participants as $participant) {
            if ($participant->id === $message->sender_id) {
                continue;
            }

            $readAt = $this->normalizeCarbon($participant->pivot->last_read_at);

            if (! $readAt || $readAt->lt($sentAt)) {
                return false;
            }
        }

        return true;
    }

    private function normalizeCarbon($value): ?Carbon
    {
        if ($value instanceof Carbon) {
            return $value;
        }

        if (is_string($value) && trim($value) !== '') {
            return Carbon::parse($value);
        }

        return null;
    }
}
