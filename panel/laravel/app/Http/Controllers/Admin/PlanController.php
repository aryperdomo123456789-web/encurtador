<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class PlanController extends Controller
{
    public function index(Request $request): View
    {
        $this->requireOwner($request);

        $plans = Plan::query()
            ->withCount('subscriptions')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('admin.plans.index', [
            'plans' => $plans,
            'summary' => [
                'active' => $plans->where('is_active', true)->where('is_public', true)->count(),
                'subscribers' => Subscription::query()
                    ->whereIn('status', ['active', 'trialing'])
                    ->distinct()
                    ->count('user_id'),
                'mrrCents' => (int) Subscription::query()
                    ->whereIn('subscriptions.status', ['active', 'trialing'])
                    ->join('plans', 'plans.id', '=', 'subscriptions.plan_id')
                    ->sum('plans.monthly_price_cents'),
                'withStripePrice' => $plans->whereNotNull('stripe_price_id')->count(),
            ],
        ]);
    }

    public function create(Request $request): View
    {
        $this->requireOwner($request);

        return view('admin.plans.form', [
            'plan' => null,
            'formAction' => route('admin.plans.store'),
            'formMethod' => 'POST',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->requireOwner($request);

        $data = $this->validated($request);
        $plan = Plan::query()->create($data);

        return redirect()
            ->route('admin.plans.index')
            ->with('status', 'Plano '.$plan->name.' criado no catálogo local. Sincronize o Price no Stripe Test antes de liberar checkout.');
    }

    public function edit(Request $request, Plan $plan): View
    {
        $this->requireOwner($request);

        return view('admin.plans.form', [
            'plan' => $plan,
            'formAction' => route('admin.plans.update', $plan),
            'formMethod' => 'PUT',
        ]);
    }

    public function update(Request $request, Plan $plan): RedirectResponse
    {
        $this->requireOwner($request);

        $data = $this->validated($request, $plan);
        $plan->update($data);

        return redirect()
            ->route('admin.plans.index')
            ->with('status', 'Plano '.$plan->name.' atualizado no catálogo local.');
    }

    public function archive(Request $request, Plan $plan): RedirectResponse
    {
        $this->requireOwner($request);

        if ($plan->code === 'free') {
            return back()->withErrors(['plan' => 'O plano Free é a base de segurança e não pode ser arquivado.']);
        }

        $plan->forceFill([
            'is_active' => false,
            'is_public' => false,
            'is_featured' => false,
        ])->save();

        return redirect()
            ->route('admin.plans.index')
            ->with('status', 'Plano '.$plan->name.' arquivado. O histórico de assinaturas foi preservado.');
    }

    /** @return array<string,mixed> */
    private function validated(Request $request, ?Plan $plan = null): array
    {
        $data = $request->validate([
            'code' => [
                'required',
                'string',
                'max:32',
                'regex:/^[a-z][a-z0-9_-]{1,31}$/',
                Rule::unique('plans', 'code')->ignore($plan?->id),
            ],
            'name' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:255'],
            'marketing_label' => ['nullable', 'string', 'max:120'],
            'is_free' => ['nullable', 'boolean'],
            'monthly_price_cents' => ['required', 'integer', 'min:0', 'max:100000000'],
            'currency' => ['required', 'string', 'size:3', 'in:BRL'],
            'monthly_short_url_limit' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'monthly_click_limit' => ['nullable', 'integer', 'min:0', 'max:1000000000'],
            'custom_domain_limit' => ['required', 'integer', 'min:0', 'max:10000'],
            'allow_custom_slug' => ['nullable', 'boolean'],
            'allow_custom_domain' => ['nullable', 'boolean'],
            'allow_custom_expiration' => ['nullable', 'boolean'],
            'allow_lifetime_links' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'is_public' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
            'stripe_product_id' => ['nullable', 'string', 'max:128', 'regex:/^prod_[A-Za-z0-9]+$/'],
            'stripe_price_id' => ['nullable', 'string', 'max:128', 'regex:/^price_[A-Za-z0-9]+$/'],
        ]);

        $data['is_free'] = $request->boolean('is_free');
        $data['allow_custom_slug'] = $request->boolean('allow_custom_slug');
        $data['allow_custom_domain'] = $request->boolean('allow_custom_domain');
        $data['allow_custom_expiration'] = $request->boolean('allow_custom_expiration');
        $data['allow_lifetime_links'] = $request->boolean('allow_lifetime_links');
        $data['is_active'] = $request->boolean('is_active');
        $data['is_public'] = $request->boolean('is_public');
        $data['is_featured'] = $request->boolean('is_featured');
        $data['code'] = strtolower(trim((string) $data['code']));
        $data['currency'] = strtoupper(trim((string) $data['currency']));
        $data['stripe_product_id'] = $this->nullableTrim($data['stripe_product_id'] ?? null);
        $data['stripe_price_id'] = $this->nullableTrim($data['stripe_price_id'] ?? null);

        $validator = Validator::make($data, []);
        $validator->after(function ($validator) use ($data): void {
            if ($data['is_free'] && (int) $data['monthly_price_cents'] !== 0) {
                $validator->errors()->add('monthly_price_cents', 'O plano gratuito deve ter preço R$ 0,00.');
            }
            if (! $data['is_free'] && (int) $data['monthly_price_cents'] < 1) {
                $validator->errors()->add('monthly_price_cents', 'Planos pagos precisam ter preço maior que zero.');
            }
            if ($data['is_free'] && (($data['stripe_product_id'] ?? null) !== null || ($data['stripe_price_id'] ?? null) !== null)) {
                $validator->errors()->add('stripe_price_id', 'O plano Free não deve possuir IDs Stripe.');
            }
            if (! $data['allow_custom_domain'] && (int) $data['custom_domain_limit'] > 0) {
                $validator->errors()->add('custom_domain_limit', 'Defina limite zero quando o domínio próprio estiver desativado.');
            }
        });
        $validator->validate();

        return $data;
    }

    private function requireOwner(Request $request): void
    {
        abort_unless((bool) optional($request->user())->isOwner(), 403);
    }

    private function nullableTrim(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value !== '' ? $value : null;
    }
}
