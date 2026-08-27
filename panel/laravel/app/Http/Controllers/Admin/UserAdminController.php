<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomerDomain;
use App\Models\ShortLink;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

final class UserAdminController extends Controller
{
    private function requireOwner(Request $request): void
    {
        abort_unless((bool) optional($request->user())->isOwner(), 403);
    }

    public function index(Request $request): View
    {
        $this->requireOwner($request);

        $search = trim((string) $request->string('search'));

        $users = User::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($nested) use ($search): void {
                    $nested->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                });
            })
            ->withCount([
                'subscriptions',
                'customerDomains',
                'shortLinks',
            ])
            ->orderByRaw("CASE WHEN role = 'owner' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'search' => $search,
            'summary' => [
                'total' => User::query()->count(),
                'owners' => User::query()->where('role', 'owner')->count(),
                'common' => User::query()->where('role', 'user')->count(),
                'premium' => Subscription::query()
                    ->whereIn('status', ['active', 'trialing'])
                    ->distinct()
                    ->count('user_id'),
                'domains' => CustomerDomain::query()->count(),
                'links' => ShortLink::query()->count(),
            ],
        ]);
    }

    public function show(Request $request, User $user): View
    {
        $this->requireOwner($request);

        $user->loadCount(['subscriptions', 'customerDomains', 'shortLinks']);

        return view('admin.users.show', [
            'user' => $user,
            'subscriptions' => Subscription::query()
                ->with('plan')
                ->where('user_id', $user->id)
                ->latest('id')
                ->limit(5)
                ->get(),
            'links' => ShortLink::query()
                ->where('user_id', $user->id)
                ->latest('id')
                ->limit(10)
                ->get(),
            'domains' => CustomerDomain::query()
                ->where('user_id', $user->id)
                ->latest('id')
                ->limit(10)
                ->get(),
        ]);
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $this->requireOwner($request);

        if ($user->isOwner()) {
            return back()->withErrors(['user' => 'Nao e permitido resetar a conta do dono por aqui.']);
        }

        $temporaryPassword = Str::random(12);

        $user->forceFill([
            'password' => Hash::make($temporaryPassword),
            'remember_token' => Str::random(10),
        ])->save();

        return redirect()
            ->route('admin.users.show', $user)
            ->with('status', 'Senha temporaria gerada: '.$temporaryPassword);
    }
}
