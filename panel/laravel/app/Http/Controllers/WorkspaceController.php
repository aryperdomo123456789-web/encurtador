<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

final class WorkspaceController extends Controller
{
    public function index(Request $request): View
    {
        $workspaces = $request->user()->workspaces()->withCount('members')->get();
        $currentWorkspace = $this->currentWorkspace($request, $workspaces->first());

        return view('workspaces.index', compact('workspaces', 'currentWorkspace'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isPremium(), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
        ]);
        $name = trim((string) $data['name']);
        $workspace = Workspace::query()->create([
            'owner_user_id' => $request->user()->id,
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(6)),
            'status' => 'active',
        ]);
        $workspace->members()->attach($request->user()->id, ['role' => 'owner']);
        $request->session()->put('workspace_id', $workspace->id);

        return back()->with('status', 'Workspace criado. Você já pode convidar sua equipe.');
    }

    public function switch(Request $request, Workspace $workspace): RedirectResponse
    {
        abort_unless($this->isMember($request->user(), $workspace), 404);
        $request->session()->put('workspace_id', $workspace->id);

        return back()->with('status', 'Workspace ativo: '.$workspace->name.'.');
    }

    public function addMember(Request $request, Workspace $workspace): RedirectResponse
    {
        abort_unless($this->canManage($request->user(), $workspace), 403);

        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', 'string', 'in:admin,member,viewer'],
        ]);
        $member = User::query()->where('email', strtolower(trim((string) $data['email'])))->first();
        if ($member === null) {
            return back()->withErrors(['email' => 'A pessoa precisa criar uma conta MElink antes de ser adicionada.']);
        }

        $workspace->members()->syncWithoutDetaching([
            $member->id => ['role' => $data['role']],
        ]);

        return back()->with('status', 'Membro adicionado ao workspace.');
    }

    public function removeMember(Request $request, Workspace $workspace, User $member): RedirectResponse
    {
        abort_unless($this->canManage($request->user(), $workspace), 403);
        abort_if((int) $member->id === (int) $workspace->owner_user_id, 422, 'O proprietário não pode ser removido.');
        $workspace->members()->detach($member->id);

        return back()->with('status', 'Membro removido do workspace.');
    }

    private function currentWorkspace(Request $request, ?Workspace $fallback): ?Workspace
    {
        $workspaceId = (int) $request->session()->get('workspace_id', 0);
        $workspace = $request->user()->workspaces()->whereKey($workspaceId)->first();

        return $workspace ?? $fallback;
    }

    private function isMember(User $user, Workspace $workspace): bool
    {
        return $user->workspaces()->whereKey($workspace->id)->exists();
    }

    private function canManage(User $user, Workspace $workspace): bool
    {
        if (! $this->isMember($user, $workspace)) {
            return false;
        }

        return in_array((string) $workspace->members()->whereKey($user->id)->first()?->pivot?->role, ['owner', 'admin'], true);
    }
}
