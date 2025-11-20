<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User;
use App\Models\Company;
use App\Models\CompanyUserPermission;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $current = request()->user();
        $role = strtolower((string) ($current->rol ?? ''));
        $isSuper = $role === 'super-admin';
        $isadmin = $isSuper || in_array($role, ['admin', 'administrador']);

        $query = User::with(['companies:id,nombre', 'companyPermissions'])
            ->select('id', 'name', 'username', 'rol', 'email', 'habilitado');

        if ($isSuper) {
            // no restrictions
        } elseif ($isadmin) {
            $query->whereIn('rol', ['analista', 'usuario']);
            $adminCompanyIds = $current->companies()->pluck('companies.id');
            if ($adminCompanyIds->count() > 0) {
                $query->whereHas('companies', function ($q) use ($adminCompanyIds) {
                    $q->whereIn('companies.id', $adminCompanyIds);
                });
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $users = $query->orderBy('id')->get()->map(function ($u) {
            $permissions = $u->companyPermissions->mapWithKeys(function ($perm) {
                return [
                    (string)$perm->company_id => [
                        'can_view_annexes' => (bool)$perm->can_view_annexes,
                        'can_upload_annexes' => (bool)$perm->can_upload_annexes,
                        'can_delete_annexes' => (bool)$perm->can_delete_annexes,
                        'can_generate_documents' => (bool)$perm->can_generate_documents,
                    ],
                ];
            });

            return [
                'id' => $u->id,
                'nombre' => $u->name,
                'username' => $u->username,
                'rol' => $u->rol,
                'correo' => $u->email,
                'habilitado' => (bool) $u->habilitado,
                'empresasAsociadas' => $u->companies->pluck('id')->values(),
                'permisos' => (object)$permissions,
            ];
        });

        $companies = Company::select('id', 'nombre', 'nit_empresa as nit', 'logo_izquierdo as logo')
            ->orderBy('nombre')
            ->get();

        return Inertia::render('UsersList', [
            'users' => $users,
            'companies' => $companies,
            'canCreate' => $isadmin,
        ]);
    }

    /**
     * Update companies assigned to a user.
     */
    public function updateCompanies(Request $request, int $id)
    {
        $user = User::findOrFail($id);

        // No permitir cambiar asignaciones de un super-admin
        if (strtolower((string) $user->rol) === 'super-admin') {
            abort(403, 'No se pueden modificar empresas de un super-admin');
        }

        $validated = $request->validate([
            'company_ids' => ['array'],
            'company_ids.*' => ['integer', 'exists:companies,id'],
        ]);

        $ids = $validated['company_ids'] ?? [];
        if (strtolower((string) $user->rol) === 'usuario' && count($ids) > 1) {
            return back()->withErrors(['company_ids' => 'Un usuario solo puede tener una empresa asignada']);
        }
        $user->companies()->sync($ids);
        $this->syncCompanyPermissions($user, $ids);

        return back()->with('success', 'Empresas asignadas actualizadas');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $current = $request->user();
        $role = strtolower((string) ($current->rol ?? ''));
        if (!in_array($role, ['admin', 'administrador', 'super-admin'])) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'rol' => ['required', 'string'],
            'company_ids' => ['array'],
            'company_ids.*' => ['integer', 'exists:companies,id'],
            'permissions' => ['array'],
        ]);

        $rolLower = strtolower($validated['rol']);
        if ($rolLower === 'usuario' && !empty($validated['company_ids']) && count($validated['company_ids']) > 1) {
            return back()->withErrors(['company_ids' => 'Un usuario solo puede tener una empresa asignada'])->withInput();
        }

        $user = new User();
        $user->name = $validated['name'];
        $user->username = $validated['username'];
        $user->email = $validated['email'];
        $user->rol = $validated['rol'];
        $user->password = $validated['password'];
        $user->habilitado = true;
        $user->save();

        $companyIds = $validated['company_ids'] ?? [];
        if (!empty($companyIds)) {
            $user->companies()->sync($companyIds);
            $this->syncCompanyPermissions($user, $companyIds);
            $user->load('companies');
            $this->applyCompanyPermissions($user, $request->input('permissions', []));
        }

        return back()->with('success', 'Usuario creado');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $current = $request->user();
        $role = strtolower((string) ($current->rol ?? ''));
        if (!in_array($role, ['admin', 'administrador', 'super-admin'])) {
            abort(403);
        }

        $user = User::findOrFail($id);
        if (strtolower((string) $user->rol) === 'super-admin') {
            abort(403, 'No se permite modificar al super-admin');
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'username' => ['sometimes', 'string', 'max:255', Rule::unique('users', 'username')->ignore($user->id)],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:6'],
            'rol' => ['sometimes', 'string'],
            'company_ids' => ['array'],
            'company_ids.*' => ['integer', 'exists:companies,id'],
            'permissions' => ['array'],
        ]);

        if (array_key_exists('rol', $validated)) {
            $rolLower = strtolower($validated['rol']);
            $companyIds = $request->input('company_ids', []);
            if ($rolLower === 'usuario' && is_array($companyIds) && count($companyIds) > 1) {
                return back()->withErrors(['company_ids' => 'Un usuario solo puede tener una empresa asignada'])->withInput();
            }
        }

        $user->fill(array_filter([
            'name' => $validated['name'] ?? null,
            'username' => $validated['username'] ?? null,
            'email' => $validated['email'] ?? null,
            'rol' => $validated['rol'] ?? null,
        ], fn($v) => !is_null($v)));

        if (!empty($validated['password'])) {
            $user->password = $validated['password'];
        }

        $user->save();

        if ($request->has('company_ids')) {
            $companyIds = $request->input('company_ids', []);
            $user->companies()->sync($companyIds);
            $this->syncCompanyPermissions($user, $companyIds);
        }

        if ($request->has('permissions')) {
            $user->loadMissing('companies');
            $this->applyCompanyPermissions($user, $request->input('permissions', []));
        }

        return back()->with('success', 'Usuario actualizado');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $user = User::findOrFail($id);
            if (strtolower((string) $user->rol) === 'super-admin') {
                return back()->withErrors(['delete' => 'No se puede eliminar al super-admin']);
            }
            $user->companies()->detach();
            CompanyUserPermission::where('user_id', $user->id)->delete();
            $user->delete();
            return back()->with('success', 'Usuario eliminado');
        } catch (\Throwable $e) {
            return back()->withErrors(['delete' => 'No se pudo eliminar el usuario: ' . $e->getMessage()]);
        }
    }

    /**
     * Update enabled/disabled (habilitado) status for a user.
     */
    public function updateStatus(Request $request, int $id)
    {
        $user = User::findOrFail($id);
        $habilitado = $request->has('habilitado')
            ? filter_var($request->input('habilitado'), FILTER_VALIDATE_BOOLEAN)
            : ! (bool) $user->habilitado;
        $user->habilitado = $habilitado;
        $user->save();

        return back()->with('success', 'Estado actualizado');
    }

    public function updateCompanyPermissions(Request $request, int $id)
    {
        $current = $request->user();
        $role = strtolower((string) ($current->rol ?? ''));
        if (!in_array($role, ['admin', 'administrador', 'super-admin'])) {
            abort(403);
        }

        $user = User::with('companies')->findOrFail($id);
        if (strtolower((string) $user->rol) === 'super-admin') {
            abort(403, 'No se puede modificar al super-admin');
        }

        $payload = $request->validate([
            'permissions' => ['required', 'array'],
        ]);

        $assignedCompanyIds = $user->companies->pluck('id')->map(fn($id) => (int)$id)->toArray();

        $this->applyCompanyPermissions($user, $payload['permissions']);

        return back()->with('success', 'Permisos actualizados');
    }

    private function syncCompanyPermissions(User $user, array $companyIds): void
    {
        $companyIds = array_map('intval', $companyIds);
        CompanyUserPermission::where('user_id', $user->id)
            ->whereNotIn('company_id', $companyIds)
            ->delete();

        foreach ($companyIds as $companyId) {
            CompanyUserPermission::firstOrCreate(
                ['company_id' => $companyId, 'user_id' => $user->id],
                [
                    'can_view_annexes' => true,
                    'can_upload_annexes' => false,
                    'can_delete_annexes' => false,
                    'can_generate_documents' => false,
                ]
            );
        }
    }

    private function normalizePermissions(array $perms): array
    {
        $view = (bool)($perms['can_view_annexes'] ?? false);
        $upload = $view && (bool)($perms['can_upload_annexes'] ?? false);
        $delete = $upload && (bool)($perms['can_delete_annexes'] ?? false);
        $generate = $view && (bool)($perms['can_generate_documents'] ?? false);

        return [
            'can_view_annexes' => $view,
            'can_upload_annexes' => $upload,
            'can_delete_annexes' => $delete,
            'can_generate_documents' => $generate,
        ];
    }

    private function applyCompanyPermissions(User $user, array $permissions): void
    {
        if (empty($permissions)) {
            return;
        }

        $user->loadMissing('companies');
        $assigned = $user->companies->pluck('id')->map(fn ($id) => (int) $id)->toArray();

        foreach ($permissions as $companyId => $perms) {
            $companyId = (int) $companyId;
            if (!in_array($companyId, $assigned, true)) {
                continue;
            }

            $normalized = $this->normalizePermissions(is_array($perms) ? $perms : []);

            CompanyUserPermission::updateOrCreate(
                [
                    'company_id' => $companyId,
                    'user_id' => $user->id,
                ],
                $normalized
            );
        }
    }
}
