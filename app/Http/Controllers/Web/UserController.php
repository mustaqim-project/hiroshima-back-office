<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * @req FR-AUTH-01, FR-AUTH-03, FR-AUTH-04, FSD-01
 */
class UserController extends Controller
{
    public function __construct(
        protected AuditLogger $auditLogger
    ) {}

    public function index(): View
    {
        $users = User::with('branch')->orderBy('id')->get();
        return view('backoffice.users.index', compact('users'));
    }

    public function create(): View
    {
        $branches = Branch::where('is_active', true)->get();
        $roles = config('rbac.roles', []);
        return view('backoffice.users.create', compact('branches', 'roles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'username' => 'required|string|max:50|unique:users,username',
            'email' => 'required|email|max:100|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:6',
            'pin' => 'nullable|string|size:6|regex:/^\d{6}$/',
            'branch_id' => 'nullable|exists:branches,id',
            'role' => 'required|in:owner,central_admin,branch_manager,warehouse_staff,cashier',
            'is_active' => 'required|boolean',
        ]);

        $pinHash = ! empty($validated['pin']) ? password_hash($validated['pin'], PASSWORD_ARGON2ID) : null;

        $user = User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
            'pin_hash' => $pinHash,
            'branch_id' => $validated['branch_id'],
            'role' => $validated['role'],
            'is_active' => $validated['is_active'],
        ]);

        // Assign Spatie role with team id (branch or global 0)
        $teamId = in_array($validated['role'], ['owner', 'central_admin']) ? 0 : ($validated['branch_id'] ?? 1);
        setPermissionsTeamId($teamId);
        $user->assignRole($validated['role']);

        $this->auditLogger->record('user.created', $user, [], ['username' => $user->username, 'role' => $user->role]);

        return redirect()->route('users.index')->with('success', "Pengguna {$user->name} ({$user->username}) berhasil ditambahkan.");
    }

    public function edit(User $user): View
    {
        $branches = Branch::where('is_active', true)->get();
        $roles = config('rbac.roles', []);
        return view('backoffice.users.edit', compact('user', 'branches', 'roles'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'username' => 'required|string|max:50|unique:users,username,' . $user->id,
            'email' => 'required|email|max:100|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:6',
            'pin' => 'nullable|string|size:6|regex:/^\d{6}$/',
            'branch_id' => 'nullable|exists:branches,id',
            'role' => 'required|in:owner,central_admin,branch_manager,warehouse_staff,cashier',
            'is_active' => 'required|boolean',
        ]);

        $updateData = [
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'branch_id' => $validated['branch_id'],
            'role' => $validated['role'],
            'is_active' => $validated['is_active'],
        ];

        if (! empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        if (! empty($validated['pin'])) {
            $updateData['pin_hash'] = password_hash($validated['pin'], PASSWORD_ARGON2ID);
        }

        $user->update($updateData);

        // Sync Spatie role
        $teamId = in_array($validated['role'], ['owner', 'central_admin']) ? 0 : ($validated['branch_id'] ?? 1);
        setPermissionsTeamId($teamId);
        $user->syncRoles([$validated['role']]);

        $this->auditLogger->record('user.updated', $user, [], ['role' => $user->role]);

        return redirect()->route('users.index')->with('success', "Pengguna {$user->name} berhasil diperbarui.");
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $name = $user->name;
        $user->delete(); // Soft delete
        $this->auditLogger->record('user.deleted', $user, ['deleted' => false], ['deleted' => true]);

        return redirect()->route('users.index')->with('success', "Pengguna {$name} berhasil dihapus (soft delete).");
    }
}
