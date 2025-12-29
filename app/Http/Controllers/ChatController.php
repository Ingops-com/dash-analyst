<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $conversationId = $request->query('conversation');

        $conversations = Conversation::with(['participants', 'latestMessage.sender'])
            ->forUser($user->id)
            ->orderBy('updated_at', 'desc')
            ->get();

        $selectedConversation = $this->pickSelectedConversation($conversations, $conversationId);

        if ($selectedConversation) {
            $selectedConversation->loadMissing(['messages.sender']);
            $selectedConversation->markAsRead($user->id);
            $selectedConversation->load('participants');
        }

        $conversationData = $conversations
            ->map(fn (Conversation $conversation) => $this->formatConversation($conversation, $user->id))
            ->values();

        $availableUsers = User::select('id', 'name')
            ->where('id', '!=', $user->id)
            ->orderBy('name')
            ->get()
            ->map(fn (User $item) => ['id' => $item->id, 'name' => $item->name])
            ->values();

        return Inertia::render('Chats/Index', [
            'conversations' => $conversationData,
            'selectedConversation' => $selectedConversation
                ? $this->formatConversation($selectedConversation, $user->id, true)
                : null,
            'users' => $availableUsers,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'recipient_id' => 'required|integer|exists:users,id|different:' . $user->id,
        ]);

        $conversation = Conversation::getOrCreateForUsers([
            $user->id,
            (int) $request->input('recipient_id'),
        ]);

        return redirect()->route('chats.index', ['conversation' => $conversation->id]);
    }

    public function show(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();
        abort_unless($conversation->hasParticipant($user->id), 403);

        $conversation->loadMissing(['participants', 'messages.sender']);
        $conversation->markAsRead($user->id);

        return response()->json($this->formatConversation($conversation, $user->id, true));
    }

    public function storeMessage(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();
        abort_unless($conversation->hasParticipant($user->id), 403);

        $request->validate([
            'body' => 'required|string|max:4000',
        ]);

        $message = $conversation->messages()->create([
            'sender_id' => $user->id,
            'body' => $request->input('body'),
        ]);

        $conversation->markAsRead($user->id);
        $conversation->loadMissing(['participants']);
        MessageSent::dispatch($message);

        return response()->json([
            'message' => $this->formatMessage($conversation, $message, $user->id),
        ]);
    }

    private function formatConversation(Conversation $conversation, int $currentUserId, bool $withMessages = false): array
    {
        $conversation->loadMissing(['participants', 'latestMessage.sender']);

        if ($withMessages) {
            $conversation->loadMissing(['messages.sender']);
        }

        $payload = [
            'id' => $conversation->id,
            'participants' => $conversation->participants->map(fn (User $participant) => [
                'id' => $participant->id,
                'name' => $participant->name,
            ])->values(),
            'latest_message' => $conversation->latestMessage
                ? $this->formatMessage($conversation, $conversation->latestMessage, $currentUserId)
                : null,
            'updated_at' => $conversation->updated_at?->toDateTimeString(),
            'unread_count' => $conversation->unreadCountFor($currentUserId),
        ];

        if ($withMessages) {
            $payload['messages'] = $conversation->messages
                ->sortBy('created_at')
                ->map(fn (ChatMessage $message) => $this->formatMessage($conversation, $message, $currentUserId))
                ->values()
                ->toArray();
        }

        return $payload;
    }

    private function formatMessage(Conversation $conversation, ChatMessage $message, int $currentUserId): array
    {
        $sender = $message->sender;

        return [
            'id' => $message->id,
            'body' => $message->body,
            'sender_id' => $message->sender_id,
            'sender_name' => $sender?->name ?? 'Usuario',
            'created_at' => $message->created_at?->toDateTimeString(),
            'sent_by_me' => $message->sender_id === $currentUserId,
            'seen' => $conversation->seenByRecipients($message),
        ];
    }

    /**
     * Pick a conversation from the collection either by query parameter or default to the first.
     */
    private function pickSelectedConversation($conversations, $selectedId): ?Conversation
    {
        if ($selectedId) {
            return $conversations->firstWhere('id', (int) $selectedId);
        }

        return $conversations->first();
    }
}
