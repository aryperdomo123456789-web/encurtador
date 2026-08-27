<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ConversionEvent;
use App\Models\ShortLink;
use App\Models\User;
use App\Models\Workspace;
use App\Support\Shlink\AnalyticsService;
use App\Support\Shlink\LinkProvisioner;
use App\Support\Shlink\ShlinkClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

final class LinkApiController extends Controller
{
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'premium' => $user->isPremium(),
            ],
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'status' => ['nullable', 'string', 'in:active,expired,deleted,queued'],
            'workspace_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $workspace = $this->resolveWorkspace($request->user(), $data['workspace_id'] ?? null);
        $perPage = (int) ($data['per_page'] ?? 25);
        $links = ShortLink::query()
            ->when(
                $workspace !== null,
                fn ($query) => $query->where('workspace_id', $workspace->id),
                fn ($query) => $query->where('user_id', $request->user()->id)
            )
            ->when(isset($data['status']), fn ($query) => $query->where('status', $data['status']))
            ->latest('id')
            ->paginate($perPage);

        return response()->json([
            'data' => $links->getCollection()->map(fn (ShortLink $link): array => $this->serializeLink($link))->values(),
            'meta' => [
                'current_page' => $links->currentPage(),
                'last_page' => $links->lastPage(),
                'per_page' => $links->perPage(),
                'total' => $links->total(),
            ],
        ]);
    }

    public function store(Request $request, LinkProvisioner $provisioner): JsonResponse
    {
        $data = $request->validate([
            'long_url' => ['required', 'url', 'max:2048'],
            'workspace_id' => ['nullable', 'integer', 'min:1'],
            'custom_slug' => ['nullable', 'string', 'regex:/^[a-z0-9][a-z0-9-]{1,38}[a-z0-9]$/'],
            'domain' => ['nullable', 'string', 'max:190'],
            'title' => ['nullable', 'string', 'max:120'],
            'tags' => ['nullable', 'array', 'max:10'],
            'tags.*' => ['string', 'max:50'],
            'utm_source' => ['nullable', 'string', 'max:100', 'regex:/^[^&=#]+$/'],
            'utm_medium' => ['nullable', 'string', 'max:100', 'regex:/^[^&=#]+$/'],
            'utm_campaign' => ['nullable', 'string', 'max:100', 'regex:/^[^&=#]+$/'],
            'utm_term' => ['nullable', 'string', 'max:100', 'regex:/^[^&=#]+$/'],
            'utm_content' => ['nullable', 'string', 'max:100', 'regex:/^[^&=#]+$/'],
            'forward_query' => ['nullable', 'boolean'],
            'valid_until' => ['nullable', 'date', 'after:now', 'before:+1 year'],
        ]);

        $workspace = $this->resolveWorkspace($request->user(), $data['workspace_id'] ?? null);
        if ($workspace !== null && ! $this->hasWorkspaceRole($request->user(), $workspace, ['owner', 'admin', 'member'])) {
            return response()->json([
                'error' => 'workspace_read_only',
                'message' => 'Seu papel não permite criar links neste workspace.',
            ], 403);
        }

        $userId = (int) $request->user()->id;
        $trackedUrl = $this->appendUtmParameters((string) $data['long_url'], $data);
        $hasPremiumOptions = ! empty($data['custom_slug']) || ! empty($data['domain']) || ! empty($data['valid_until']);

        if ($hasPremiumOptions && ! $request->user()->isPremium()) {
            return response()->json([
                'error' => 'premium_required',
                'message' => 'custom_slug, domain e valid_until exigem um plano Premium.',
            ], 422);
        }

        try {
            if ($hasPremiumOptions) {
                $response = $provisioner->createPremiumLink(
                    $userId,
                    $trackedUrl,
                    (string) $data['custom_slug'],
                    [
                        'workspaceId' => $workspace?->id,
                        'domain' => $data['domain'] ?? null,
                        'title' => $data['title'] ?? null,
                        'tags' => $data['tags'] ?? null,
                        'forwardQuery' => (bool) ($data['forward_query'] ?? false),
                        'validUntil' => $data['valid_until'] ?? null,
                    ]
                );
            } else {
                $response = $provisioner->createFreeLink($userId, $trackedUrl, [
                    'findIfExists' => true,
                    'workspaceId' => $workspace?->id,
                ]);
            }
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'error' => 'link_creation_failed',
                'message' => 'Não foi possível criar o link agora.',
            ], 422);
        }

        return response()->json(['data' => [
            'short_code' => $response['shortCode'] ?? null,
            'short_url' => $response['shortUrl'] ?? null,
            'long_url' => $trackedUrl,
        ]], 201);
    }

    public function show(Request $request, ShortLink $link): JsonResponse
    {
        abort_unless($this->canAccessLink($request->user(), $link), 404);

        return response()->json(['data' => $this->serializeLink($link)]);
    }

    public function analytics(Request $request, ShortLink $link, AnalyticsService $analytics): JsonResponse
    {
        abort_unless($this->canAccessLink($request->user(), $link), 404);

        $data = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'exclude_bots' => ['nullable', 'boolean'],
        ]);

        try {
            $visits = $analytics->getShortUrlVisits((string) $link->shlink_short_code, [
                'startDate' => $data['start_date'] ?? null,
                'endDate' => $data['end_date'] ?? null,
                'page' => $data['page'] ?? 1,
                'itemsPerPage' => $data['per_page'] ?? 25,
                'excludeBots' => (bool) ($data['exclude_bots'] ?? true),
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'error' => 'analytics_unavailable',
                'message' => 'Analytics indisponível no momento.',
            ], 503);
        }

        return response()->json([
            'data' => [
                'link' => $this->serializeLink($link),
                'visits' => $visits,
            ],
        ]);
    }

    public function trackEvent(Request $request): JsonResponse
    {
        $data = $request->validate([
            'event_type' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9_.-]+$/'],
            'event_id' => ['nullable', 'string', 'max:120'],
            'short_code' => ['nullable', 'string', 'max:190'],
            'workspace_id' => ['nullable', 'integer', 'min:1'],
            'occurred_at' => ['nullable', 'date'],
            'properties' => ['nullable', 'array', 'max:30'],
        ]);

        $workspace = $this->resolveWorkspace($request->user(), $data['workspace_id'] ?? null);
        $link = null;
        if (! empty($data['short_code'])) {
            $link = ShortLink::query()
                ->when(
                    $workspace !== null,
                    fn ($query) => $query->where('workspace_id', $workspace->id),
                    fn ($query) => $query->where('user_id', $request->user()->id)
                )
                ->where('shlink_short_code', $data['short_code'])
                ->first();
            abort_unless($link instanceof ShortLink, 404);
        }

        $workspaceId = $workspace?->id ?? $link?->workspace_id;
        if ($workspaceId !== null && $workspace === null) {
            $workspace = $this->resolveWorkspace($request->user(), $workspaceId);
        }

        $event = ConversionEvent::query()->firstOrCreate(
            [
                'workspace_id' => $workspace?->id,
                'event_id' => $data['event_id'] ?? null,
            ],
            [
                'user_id' => $request->user()->id,
                'short_link_id' => $link?->id,
                'event_type' => $data['event_type'],
                'occurred_at' => $data['occurred_at'] ?? now(),
                'properties' => $data['properties'] ?? [],
                'ip_hash' => hash_hmac('sha256', (string) $request->ip(), (string) config('app.key')),
                'user_agent_hash' => hash_hmac('sha256', (string) $request->userAgent(), (string) config('app.key')),
            ]
        );

        return response()->json([
            'data' => [
                'id' => $event->id,
                'event_type' => $event->event_type,
                'event_id' => $event->event_id,
                'created' => $event->wasRecentlyCreated,
            ],
        ], $event->wasRecentlyCreated ? 201 : 200);
    }

    public function update(Request $request, ShortLink $link, ShlinkClient $client): JsonResponse
    {
        abort_unless($this->canAccessLink($request->user(), $link), 404);
        if ($link->workspace_id !== null && ! $this->hasWorkspaceRole($request->user(), $link->workspace, ['owner', 'admin', 'member'])) {
            return response()->json([
                'error' => 'workspace_read_only',
                'message' => 'Seu papel não permite editar links neste workspace.',
            ], 403);
        }

        $data = $request->validate([
            'long_url' => ['required', 'url', 'max:2048'],
        ]);

        try {
            $response = $client->updateShortUrl((string) $link->shlink_short_code, [
                'longUrl' => (string) $data['long_url'],
            ]);
            $link->update([
                'long_url' => $data['long_url'],
                'updated_by_user_id' => $request->user()->id,
                'shlink_response' => array_merge((array) $link->shlink_response, ['last_update' => $response]),
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'error' => 'link_update_failed',
                'message' => 'Não foi possível atualizar o destino agora.',
            ], 502);
        }

        return response()->json(['data' => $this->serializeLink($link->fresh())]);
    }

    public function destroy(Request $request, ShortLink $link, ShlinkClient $client): JsonResponse
    {
        abort_unless($this->canAccessLink($request->user(), $link), 404);
        if ($link->workspace_id !== null && ! $this->hasWorkspaceRole($request->user(), $link->workspace, ['owner', 'admin', 'member'])) {
            return response()->json([
                'error' => 'workspace_read_only',
                'message' => 'Seu papel não permite excluir links neste workspace.',
            ], 403);
        }

        try {
            if ($link->shlink_short_code !== null) {
                $client->deleteShortUrl((string) $link->shlink_short_code);
            }
            $link->delete();
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'error' => 'link_deletion_failed',
                'message' => 'Não foi possível excluir o link agora.',
            ], 503);
        }

        return response()->json(null, 204);
    }

    /** @return array<string,mixed> */
    private function serializeLink(ShortLink $link): array
    {
        return [
            'id' => $link->id,
            'workspace_id' => $link->workspace_id,
            'short_code' => $link->shlink_short_code,
            'short_url' => $link->shlink_short_url,
            'domain' => $link->domain,
            'long_url' => $link->long_url,
            'status' => $link->status,
            'is_free' => (bool) $link->is_free_link,
            'valid_until' => optional($link->valid_until)->toISOString(),
            'created_at' => optional($link->created_at)->toISOString(),
        ];
    }

    private function resolveWorkspace(User $user, mixed $workspaceId): ?Workspace
    {
        $query = $user->workspaces()->where('workspaces.status', 'active');
        if ($workspaceId !== null) {
            return $query->whereKey((int) $workspaceId)->firstOrFail();
        }

        return $query->orderBy('workspaces.id')->first();
    }

    /** @param list<string> $roles */
    private function hasWorkspaceRole(User $user, Workspace $workspace, array $roles): bool
    {
        $role = $workspace->members()->whereKey($user->id)->first()?->pivot?->role;

        return in_array((string) $role, $roles, true);
    }

    private function canAccessLink(User $user, ShortLink $link): bool
    {
        if ((int) $link->user_id === (int) $user->id) {
            return true;
        }

        return $link->workspace_id !== null
            && $user->workspaces()->whereKey($link->workspace_id)->exists();
    }

    /** @param array<string,mixed> $data */
    private function appendUtmParameters(string $url, array $data): string
    {
        $utm = [];
        foreach (['source', 'medium', 'campaign', 'term', 'content'] as $key) {
            $value = isset($data['utm_'.$key]) ? trim((string) $data['utm_'.$key]) : '';
            if ($value !== '') {
                $utm['utm_'.$key] = $value;
            }
        }

        if ($utm === []) {
            return $url;
        }

        $parts = parse_url($url);
        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            return $url;
        }

        parse_str((string) ($parts['query'] ?? ''), $query);
        $query = array_merge($query, $utm);
        $rebuilt = $parts['scheme'].'://'.($parts['host'] ?? '');
        if (isset($parts['port'])) {
            $rebuilt .= ':'.$parts['port'];
        }
        $rebuilt .= $parts['path'] ?? '';
        if ($query !== []) {
            $rebuilt .= '?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        }
        if (isset($parts['fragment'])) {
            $rebuilt .= '#'.$parts['fragment'];
        }

        return $rebuilt;
    }
}
