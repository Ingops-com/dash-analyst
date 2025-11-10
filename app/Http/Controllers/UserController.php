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
        // Fetch users with assigned companies and map fields
        $users = User::with('companies:id,nombre')
            ->select('id', 'name', 'rol', 'email', 'habilitado')
            ->orderBy('id')
            ->get()
            ->map(function ($u) {
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
        ]);
    }

    /**
     * Update companies assigned to a user.
     */
    public function updateCompanies(Request $request, int $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'company_ids' => ['array'],
            'company_ids.*' => ['integer', 'exists:companies,id'],
        ]);

        $ids = $validated['company_ids'] ?? [];
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
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
