import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Textarea } from '@/components/ui/textarea';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { MessageSquare } from 'lucide-react';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { getEcho } from '@/lib/echo';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Chats',
        href: '/chats',
    },
];

type ChatParticipant = {
    id: number;
    name: string;
};

type ChatMessage = {
    id: number;
    body: string;
    sender_id: number;
    sender_name: string | null;
    created_at: string | null;
    sent_by_me: boolean;
    seen: boolean;
};

type ChatConversation = {
    id: number;
    participants: ChatParticipant[];
    latest_message: ChatMessage | null;
    updated_at: string | null;
    unread_count: number;
    messages?: ChatMessage[];
};

type ChatConversationWithMessages = ChatConversation & {
    messages: ChatMessage[];
};

type BroadcastMessagePayload = {
    conversation_id: number;
    participants: ChatParticipant[];
    message: {
        id: number;
        body: string;
        sender_id: number;
        sender_name: string | null;
        created_at: string | null;
    };
};

interface ChatsPageProps {
    conversations: ChatConversation[];
    users: { id: number; name: string }[];
    selectedConversation: ChatConversationWithMessages | null;
}

export default function ChatsIndex({
    conversations: initialConversations,
    selectedConversation: initialSelected,
    users,
}: ChatsPageProps) {
    const page = usePage();
    const currentUserId = (page.props.auth?.user?.id ?? null) as number | null;

    const [conversations, setConversations] = useState(initialConversations);
    const [selectedConversation, setSelectedConversation] = useState(initialSelected);
    const [messageBody, setMessageBody] = useState('');
    const [recipientId, setRecipientId] = useState<number | null>(users[0]?.id ?? null);
    const messagesRef = useRef<HTMLDivElement>(null);
    const selectedConversationIdRef = useRef<number | null>(initialSelected?.id ?? null);
    const pollingControllerRef = useRef<AbortController | null>(null);

    useEffect(() => {
        setConversations(initialConversations);
    }, [initialConversations]);

    useEffect(() => {
        setSelectedConversation(initialSelected);
    }, [initialSelected]);

    useEffect(() => {
        selectedConversationIdRef.current = selectedConversation?.id ?? null;
    }, [selectedConversation?.id]);

    useEffect(() => {
        if (!selectedConversation?.id) {
            return undefined;
        }

        pollingControllerRef.current?.abort();
        const controller = new AbortController();
        pollingControllerRef.current = controller;

        const pollConversation = async () => {
            try {
                const response = await fetch(`/chats/${selectedConversation.id}`, {
                    signal: controller.signal,
                    headers: {
                        Accept: 'application/json',
                    },
                });

                if (!response.ok) {
                    return;
                }

                const data: ChatConversationWithMessages = await response.json();

                setSelectedConversation((prev) =>
                    prev && prev.id === data.id ? data : prev,
                );

                setConversations((prev) =>
                    prev.map((conversation) =>
                        conversation.id === data.id
                            ? {
                                  ...conversation,
                                  latest_message: data.latest_message,
                                  updated_at: data.updated_at,
                                  unread_count: 0,
                              }
                            : conversation,
                    ),
                );
            } catch (error) {
                if ((error as { name?: string }).name === 'AbortError') {
                    return;
                }
                console.error('Error polling chat', error);
            }
        };

        pollConversation();
        const interval = setInterval(pollConversation, 10000);

        return () => {
            controller.abort();
            clearInterval(interval);
        };
    }, [selectedConversation?.id]);

    useEffect(() => {
        if (!messagesRef.current) return;
        messagesRef.current.scrollTop = messagesRef.current.scrollHeight;
    }, [selectedConversation?.messages]);

    const formatTimestamp = useCallback((value: string | null) => {
        if (!value) return '';
        const date = new Date(value);
        return date.toLocaleString('es-CO', {
            hour: '2-digit',
            minute: '2-digit',
            day: '2-digit',
            month: 'short',
        });
    }, []);

    const partnerNames = useMemo(() => {
        if (!selectedConversation) return '';
        return selectedConversation.participants
            .filter((participant) => participant.id !== currentUserId)
            .map((participant) => participant.name)
            .join(', ');
    }, [currentUserId, selectedConversation]);

    const selectConversation = async (conversationId: number) => {
        if (selectedConversation?.id === conversationId) {
            return;
        }

        const response = await fetch(`/chats/${conversationId}`, {
            headers: {
                Accept: 'application/json',
            },
        });

        if (!response.ok) {
            alert('No se pudo cargar la conversación');
            return;
        }

        const data: ChatConversationWithMessages = await response.json();
        const { messages, ...conversationWithoutMessages } = data;

        setSelectedConversation(data);
        setConversations((prev) =>
            prev.map((item) =>
                item.id === conversationId
                    ? {
                          ...conversationWithoutMessages,
                          unread_count: 0,
                      }
                    : item,
            ),
        );
    };

    const startConversation = () => {
        if (!recipientId) return;
        router.post('/chats', { recipient_id: recipientId });
    };

    const sendMessage = async () => {
        if (!selectedConversation || !messageBody.trim()) {
            return;
        }

        const tokenElement = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = tokenElement?.getAttribute('content') ?? '';

        const response = await fetch(`/chats/${selectedConversation.id}/messages`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                Accept: 'application/json',
            },
            body: JSON.stringify({ body: messageBody }),
        });

        if (!response.ok) {
            alert('No se pudo enviar el mensaje');
            return;
        }

        const payload = await response.json();
        const newMessage: ChatMessage = payload.message;

        setMessageBody('');
        setSelectedConversation((prev) =>
            prev
                ? {
                      ...prev,
                      latest_message: newMessage,
                      updated_at: newMessage.created_at,
                      messages: [...prev.messages, newMessage],
                  }
                : prev,
        );

        setConversations((prev) =>
            prev.map((item) =>
                item.id === selectedConversation.id
                    ? {
                          ...item,
                          latest_message: newMessage,
                          updated_at: newMessage.created_at,
                          unread_count: 0,
                      }
                    : item,
            ),
        );
    };

    const handleBroadcast = useCallback((payload: BroadcastMessagePayload) => {
        const isSenderMe = payload.message.sender_id === currentUserId;
        setConversations((prev) => {
            const normalizedMessage: ChatMessage = {
                ...payload.message,
                sent_by_me: isSenderMe,
                seen: isSenderMe,
            };

            const exists = prev.some((conversation) => conversation.id === payload.conversation_id);

            if (exists) {
                return prev.map((conversation) => {
                    if (conversation.id !== payload.conversation_id) {
                        return conversation;
                    }

                const nextUnreadCount =
                    selectedConversationIdRef.current === payload.conversation_id
                        ? 0
                        : !isSenderMe
                            ? (conversation.unread_count ?? 0) + 1
                            : conversation.unread_count;

                return {
                    ...conversation,
                    latest_message: normalizedMessage,
                    updated_at: payload.message.created_at,
                    unread_count: nextUnreadCount,
                };
                });
            }

            return [
                {
                    id: payload.conversation_id,
                    participants: payload.participants,
                    latest_message: normalizedMessage,
                    updated_at: payload.message.created_at,
                    unread_count: isSenderMe ? 0 : 1,
                },
                ...prev,
            ];
        });

        setSelectedConversation((prev) => {
            if (!prev || prev.id !== payload.conversation_id) {
                return prev;
            }

            const normalizedMessage: ChatMessage = {
                ...payload.message,
                sent_by_me: isSenderMe,
                seen: isSenderMe,
            };

            return {
                ...prev,
                latest_message: normalizedMessage,
                updated_at: payload.message.created_at,
                unread_count: 0,
                messages: [...prev.messages, normalizedMessage],
            };
        });
    }, [currentUserId]);

    useEffect(() => {
        const echo = getEcho();
        if (!echo) {
            return;
        }

        const channels = new Map<number, () => void>();

        conversations.forEach((conversation) => {
            const channel = echo.private(`conversation.${conversation.id}`);
            const listener = (payload: BroadcastMessagePayload) => handleBroadcast(payload);
            channel.listen('MessageSent', listener);
            channels.set(conversation.id, () => channel.stopListening('MessageSent', listener));
        });

        return () => {
            channels.forEach((unlisten) => unlisten());
        };
    }, [conversations, handleBroadcast]);

    const hasConversations = Boolean(conversations.length);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Chats" />
            <div className="flex h-full flex-1 flex-col gap-6 px-4 py-6 lg:flex-row">
                <section className="flex w-full flex-col gap-4 lg:w-1/3">
                    <Card className="space-y-3">
                        <CardHeader>
                            <CardTitle>Nueva conversación</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3 pb-3">
                            <div className="space-y-1 text-sm text-muted-foreground">
                                <p>Elige un usuario para iniciar un chat privado.</p>
                            </div>
                            <div className="space-y-1">
                                <Select
                                    value={recipientId?.toString()}
                                    onValueChange={(value) => setRecipientId(Number(value))}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Selecciona un usuario" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {users.map((user) => (
                                            <SelectItem key={user.id} value={user.id.toString()}>
                                                {user.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <Button
                                className="w-full"
                                onClick={startConversation}
                                disabled={!recipientId}
                            >
                                Iniciar chat
                            </Button>
                        </CardContent>
                    </Card>

                    <div className="flex flex-1 flex-col overflow-hidden rounded-3xl border border-sidebar-border/70 bg-card">
                        <div className="flex items-center gap-2 border-b border-border px-4 py-3 text-sm font-semibold">
                            <MessageSquare className="h-4 w-4 text-muted-foreground" />
                            Conversaciones
                        </div>
                        <div className="flex flex-1 flex-col overflow-y-auto px-3 py-3 space-y-2">
                            {!hasConversations && (
                                <p className="text-xs text-muted-foreground">
                                    Todavía no tienes chats iniciados.
                                </p>
                            )}
                    {conversations.map((conversation) => {
                                const otherParticipants = conversation.participants.filter(
                                    (participant) => participant.id !== currentUserId,
                                );
                                const header =
                                    otherParticipants.length > 0
                                        ? otherParticipants.map((p) => p.name).join(', ')
                                        : 'Conversación abierta';

                                return (
                                    <button
                                        key={conversation.id}
                                        className={`flex w-full flex-col rounded-2xl border px-3 py-3 text-left transition ${
                                            selectedConversation?.id === conversation.id
                                                ? 'border-primary/80 bg-primary/5'
                                                : 'border-transparent hover:border-border'
                                        }`}
                                        onClick={() => selectConversation(conversation.id)}
                                    >
                                        <div className="flex items-center justify-between text-xs text-muted-foreground">
                                            <span className="font-semibold text-sm text-foreground">
                                                {header}
                                            </span>
                                            <span>
                                                {conversation.updated_at
                                                    ? formatTimestamp(conversation.updated_at)
                                                    : ''}
                                            </span>
                                        </div>
                                        <div className="mt-1 flex items-center justify-between gap-2 text-xs text-muted-foreground">
                                            <span className="truncate text-muted-foreground">
                                                {conversation.latest_message?.body ?? 'Sin mensajes'}
                                            </span>
                                            {conversation.unread_count > 0 && (
                                                <Badge variant="secondary">
                                                    {conversation.unread_count}
                                                </Badge>
                                            )}
                                        </div>
                                    </button>
                                );
                            })}
                        </div>
                    </div>
                </section>

                <section className="flex flex-1 flex-col gap-4">
                    <div className="flex flex-1 flex-col overflow-hidden rounded-3xl border border-sidebar-border/70 bg-card">
                        {selectedConversation ? (
                            <>
                                <div className="border-b border-border px-6 py-4">
                                    <div className="flex items-center justify-between gap-2">
                                        <div>
                                            <p className="text-lg font-semibold text-foreground">
                                                {partnerNames || 'Tú'}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                Conversación con{' '}
                                                {selectedConversation.participants.length} participantes
                                            </p>
                                        </div>
                                        <Badge variant="outline" className="min-w-[80px] text-xs">
                                            {selectedConversation.unread_count === 0
                                                ? 'Sin mensajes nuevos'
                                                : `${selectedConversation.unread_count} nuevos`}
                                        </Badge>
                                    </div>
                                </div>

                                <div
                                    ref={messagesRef}
                                    className="flex flex-1 flex-col gap-4 overflow-y-auto px-6 py-5 max-h-[60vh]"
                                >
                                    {selectedConversation.messages.map((message) => {
                                        const isMine = message.sent_by_me;
                                        return (
                                            <div
                                                key={message.id}
                                                className={`flex flex-col gap-1 rounded-2xl px-4 py-3 ${
                                                    isMine
                                                        ? 'self-end bg-primary/10 text-right'
                                                        : 'self-start bg-border/50 text-left'
                                                }`}
                                            >
                                                <div className="flex items-center justify-between text-[10px] uppercase tracking-wide text-muted-foreground">
                                                    <span>
                                                        {isMine ? 'Tú' : message.sender_name}
                                                    </span>
                                                    <span>{formatTimestamp(message.created_at)}</span>
                                                </div>
                                                <p className="text-sm break-words whitespace-pre-wrap">{message.body}</p>
                                                {isMine && (
                                                    <Badge
                                                        variant={message.seen ? 'secondary' : 'outline'}
                                                        className="self-end text-[11px]"
                                                    >
                                                        {message.seen ? 'Visto' : 'Por leer'}
                                                    </Badge>
                                                )}
                                            </div>
                                        );
                                    })}
                                </div>

                                <div className="border-t border-border px-6 py-4">
                                    <div className="flex flex-col gap-3">
                                        <Textarea
                                            value={messageBody}
                                            onChange={(event) => setMessageBody(event.target.value)}
                                            placeholder="Escribe tu mensaje..."
                                            className="resize-none"
                                            rows={3}
                                        />
                                        <Button onClick={sendMessage} disabled={!messageBody.trim()}>
                                            Enviar mensaje
                                        </Button>
                                    </div>
                                </div>
                            </>
                        ) : (
                            <div className="flex flex-1 flex-col items-center justify-center gap-3 px-6 text-center text-sm text-muted-foreground">
                                <MessageSquare className="h-6 w-6 text-muted-foreground" />
                                <p>Selecciona un chat para comenzar a conversar.</p>
                            </div>
                        )}
                    </div>
                </section>
            </div>
        </AppLayout>
    );
}
