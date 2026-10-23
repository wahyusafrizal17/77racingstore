<?php

namespace App\Http\Controllers;

use App\Models\AppDirectChat;
use App\Models\User;
use App\Services\AppDirectChatService;
use App\Services\UserPresenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppDirectChatController extends Controller
{
    public function __construct(
        private readonly AppDirectChatService $chatService,
        private readonly UserPresenceService $presence,
    ) {}

    public function index(): JsonResponse
    {
        $user = $this->user();
        $this->presence->touch($user);

        return response()->json([
            'chats' => $this->chatService->inboxFor($user)->values(),
        ]);
    }

    public function presence(): JsonResponse
    {
        $this->presence->touch($this->user());

        return response()->json(['ok' => true]);
    }

    public function unread(): JsonResponse
    {
        return response()->json([
            'unread' => $this->chatService->unreadTotal($this->user()),
        ]);
    }

    public function peers(Request $request): JsonResponse
    {
        $user = $this->user();
        $this->presence->touch($user);

        return response()->json([
            'peers' => $this->chatService->peersFor(
                $user,
                trim((string) $request->input('q', '')),
            ),
        ]);
    }

    public function start(Request $request): JsonResponse
    {
        $user = $this->user();
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $peer = User::query()->findOrFail((int) $validated['user_id']);

        try {
            $chat = $this->chatService->getOrCreateBetween($user, $peer);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $this->chatService->markRead($chat, $user);
        $chat->refresh();

        return response()->json([
            'chat' => $this->chatService->serializeInboxItem($chat, $user),
            'messages' => $this->chatService->serializeMessages($chat, $user)->values(),
        ]);
    }

    public function show(AppDirectChat $chat): JsonResponse
    {
        $user = $this->user();
        $this->chatService->assertCanAccess($user, $chat);
        $this->chatService->markRead($chat, $user);
        $chat->refresh();

        return response()->json([
            'chat' => $this->chatService->serializeInboxItem($chat, $user),
            'messages' => $this->chatService->serializeMessages($chat, $user)->values(),
            'unread' => 0,
        ]);
    }

    public function messages(Request $request, AppDirectChat $chat): JsonResponse
    {
        $user = $this->user();
        $this->chatService->assertCanAccess($user, $chat);

        $afterId = $request->integer('after_id', 0);
        if ($request->boolean('mark_read')) {
            $this->chatService->markRead($chat, $user);
            $chat->refresh();
        }

        $messages = $this->chatService->serializeMessages(
            $chat,
            $user,
            $afterId > 0 ? $afterId : null,
        );

        return response()->json([
            'unread' => $chat->unreadFor((int) $user->id),
            'messages' => $messages->values(),
        ]);
    }

    public function store(Request $request, AppDirectChat $chat): JsonResponse
    {
        $user = $this->user();
        $this->chatService->assertCanAccess($user, $chat);

        $validated = $request->validate([
            'body' => ['nullable', 'string', 'max:2000'],
            'attachment' => [
                'nullable',
                'file',
                'max:10240',
                'mimes:jpg,jpeg,png,webp,gif,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,zip,rar,csv',
            ],
        ]);

        $body = trim((string) ($validated['body'] ?? ''));
        $attachment = $request->file('attachment');

        if ($body === '' && $attachment === null) {
            return response()->json(['message' => 'Pesan atau file wajib diisi.'], 422);
        }

        try {
            $message = $this->chatService->send($chat, $user, $body, $attachment);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => $this->chatService->serializeMessage($message, $user),
        ], 201);
    }

    private function user(): User
    {
        $user = auth()->user();
        abort_if($user === null, 403);

        return $user;
    }
}
