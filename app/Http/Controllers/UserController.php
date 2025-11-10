<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User;
use App\Models\Company;

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

        $query = User::with(['companies:id,nombre'])
            ->select('id', 'name', 'rol', 'email', 'habilitado');

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
            return [
                'id' => $u->id,
                'nombre' => $u->name,
                'rol' => $u->rol,
                'correo' => $u->email,
                'habilitado' => (bool) $u->habilitado,
                'empresasAsociadas' => $u->companies->pluck('id')->values(),
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
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'rol' => ['required', 'string'],
            'company_ids' => ['array'],
            'company_ids.*' => ['integer', 'exists:companies,id'],
        ]);

        $rolLower = strtolower($validated['rol']);
        if ($rolLower === 'usuario' && !empty($validated['company_ids']) && count($validated['company_ids']) > 1) {
            return back()->withErrors(['company_ids' => 'Un usuario solo puede tener una empresa asignada'])->withInput();
        }

        $user = new User();
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->rol = $validated['rol'];
        $user->password = $validated['password'];
        $user->habilitado = true;
        $user->save();

        if (!empty($validated['company_ids'])) {
            $user->companies()->sync($validated['company_ids']);
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
            'email' => ['sometimes', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['nullable', 'string', 'min:6'],
            'rol' => ['sometimes', 'string'],
            'company_ids' => ['array'],
            'company_ids.*' => ['integer', 'exists:companies,id'],
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
            'email' => $validated['email'] ?? null,
            'rol' => $validated['rol'] ?? null,
        ], fn($v) => !is_null($v)));

        if (!empty($validated['password'])) {
            $user->password = $validated['password'];
        }

        $user->save();

        if ($request->has('company_ids')) {
            $user->companies()->sync($request->input('company_ids', []));
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
}
