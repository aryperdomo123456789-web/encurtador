<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ConversionEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class PrivacyController extends Controller
{
    public function index(): View
    {
        return view('privacy.index');
    }

    public function export(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = [
            'format' => 'melink-data-export-v1',
            'generated_at' => now()->toIso8601String(),
            'account' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'email_verified_at' => $user->email_verified_at?->toIso8601String(),
                'created_at' => $user->created_at?->toIso8601String(),
            ],
            'links' => $user->shortLinks()->get([
                'id',
                'customer_domain_id',
                'shlink_short_code',
                'domain',
                'long_url',
                'custom_slug',
                'generated_slug',
                'is_custom_slug',
                'is_free_link',
                'valid_until',
                'valid_since',
                'status',
                'created_via',
                'created_at',
                'updated_at',
            ])->map(static fn ($link): array => [
                'id' => $link->id,
                'customer_domain_id' => $link->customer_domain_id,
                'short_code' => $link->shlink_short_code,
                'domain' => $link->domain,
                'long_url' => $link->long_url,
                'custom_slug' => $link->custom_slug,
                'generated_slug' => $link->generated_slug,
                'is_custom_slug' => $link->is_custom_slug,
                'is_free_link' => $link->is_free_link,
                'valid_until' => $link->valid_until?->toIso8601String(),
                'valid_since' => $link->valid_since?->toIso8601String(),
                'status' => $link->status,
                'created_via' => $link->created_via,
                'created_at' => $link->created_at?->toIso8601String(),
                'updated_at' => $link->updated_at?->toIso8601String(),
            ])->values()->all(),
            'domains' => $user->customerDomains()->get([
                'id',
                'domain',
                'status',
                'is_primary',
                'dns_target',
                'dns_verified_at',
                'tls_mode',
                'tls_status',
                'created_at',
                'updated_at',
            ])->map(static fn ($domain): array => [
                'id' => $domain->id,
                'domain' => $domain->domain,
                'status' => $domain->status,
                'is_primary' => $domain->is_primary,
                'dns_target' => $domain->dns_target,
                'dns_verified_at' => $domain->dns_verified_at?->toIso8601String(),
                'tls_mode' => $domain->tls_mode,
                'tls_status' => $domain->tls_status,
                'created_at' => $domain->created_at?->toIso8601String(),
                'updated_at' => $domain->updated_at?->toIso8601String(),
            ])->values()->all(),
            'subscriptions' => $user->subscriptions()->get([
                'id',
                'plan_id',
                'provider',
                'status',
                'current_period_start',
                'current_period_end',
                'cancel_at_period_end',
                'created_at',
                'updated_at',
            ])->map(static fn ($subscription): array => [
                'id' => $subscription->id,
                'plan_id' => $subscription->plan_id,
                'provider' => $subscription->provider,
                'status' => $subscription->status,
                'current_period_start' => $subscription->current_period_start?->toIso8601String(),
                'current_period_end' => $subscription->current_period_end?->toIso8601String(),
                'cancel_at_period_end' => $subscription->cancel_at_period_end,
                'created_at' => $subscription->created_at?->toIso8601String(),
                'updated_at' => $subscription->updated_at?->toIso8601String(),
            ])->values()->all(),
            'workspaces' => $user->workspaces()->get([
                'workspaces.id',
                'workspaces.name',
                'workspaces.slug',
                'workspaces.status',
            ])->map(static fn ($workspace): array => [
                'id' => $workspace->id,
                'name' => $workspace->name,
                'slug' => $workspace->slug,
                'status' => $workspace->status,
                'role' => $workspace->pivot->role,
            ])->values()->all(),
            'api_tokens' => $user->apiTokens()->get([
                'id',
                'name',
                'token_prefix',
                'scopes',
                'expires_at',
                'last_used_at',
                'revoked_at',
                'created_at',
            ])->map(static fn ($token): array => [
                'id' => $token->id,
                'name' => $token->name,
                'prefix' => $token->token_prefix,
                'scopes' => $token->scopes,
                'expires_at' => $token->expires_at?->toIso8601String(),
                'last_used_at' => $token->last_used_at?->toIso8601String(),
                'revoked_at' => $token->revoked_at?->toIso8601String(),
                'created_at' => $token->created_at?->toIso8601String(),
            ])->values()->all(),
            'conversion_events' => ConversionEvent::query()
                ->where('user_id', $user->id)
                ->get(['id', 'workspace_id', 'short_link_id', 'event_type', 'event_id', 'occurred_at', 'properties', 'created_at', 'updated_at'])
                ->map(static fn ($event): array => [
                    'id' => $event->id,
                    'workspace_id' => $event->workspace_id,
                    'short_link_id' => $event->short_link_id,
                    'event_type' => $event->event_type,
                    'event_id' => $event->event_id,
                    'occurred_at' => $event->occurred_at?->toIso8601String(),
                    'properties' => $event->properties,
                    'created_at' => $event->created_at?->toIso8601String(),
                    'updated_at' => $event->updated_at?->toIso8601String(),
                ])->values()->all(),
            'quota_usage' => DB::table('monthly_quota_usage')
                ->where('user_id', $user->id)
                ->orderBy('quota_month')
                ->get(['quota_month', 'free_links_created', 'free_links_rejected', 'last_free_link_at'])
                ->map(static fn ($usage): array => [
                    'month' => $usage->quota_month,
                    'free_links_created' => $usage->free_links_created,
                    'free_links_rejected' => $usage->free_links_rejected,
                    'last_free_link_at' => $usage->last_free_link_at,
                ])->values()->all(),
        ];

        return response()->json($data, 200, [
            'Content-Disposition' => 'attachment; filename="melink-data-export.json"',
            'Cache-Control' => 'no-store, private',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
