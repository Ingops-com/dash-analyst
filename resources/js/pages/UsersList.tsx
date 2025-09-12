import { useState } from 'react';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { AddUserDialog } from '@/components/add-user-dialog';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Lista de Usuarios',
        href: '/lista-usuarios',
    },
];

// Dummy data for users
const initialUsers = [
    {
        id: 1,
        nombre: 'Juan Perez',
        username: 'jperez',
        rol: 'Administrador',
        correo: 'juan.perez@example.com',
        nir_empresa: '12345',
        habilitado: true,
    },
    {
        id: 2,
        nombre: 'Maria Lopez',
        username: 'mlopez',
        rol: 'Analista',
        correo: 'maria.lopez@example.com',
        nir_empresa: '67890',
        habilitado: false,
    },
    // Add more users as needed
];

export default function UsersList() {
    const [users, setUsers] = useState(initialUsers);
    const [nameFilter, setNameFilter] = useState('');
    const [emailFilter, setEmailFilter] = useState('');
    const [isAddUserOpen, setIsAddUserOpen] = useState(false);

    const filteredUsers = users.filter(
        (user) =>
            user.nombre.toLowerCase().includes(nameFilter.toLowerCase()) &&
            user.correo.toLowerCase().includes(emailFilter.toLowerCase())
    );

    const handleAddUser = (newUser) => {
        setUsers([...users, { ...newUser, id: users.length + 1, habilitado: true }]);
    };


    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Lista de Usuarios" />
            <AddUserDialog
                isOpen={isAddUserOpen}
                onClose={() => setIsAddUserOpen(false)}
                onAddUser={handleAddUser}
            />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex justify-between items-center mb-4">
                    <div className="flex gap-2">
                        <Input
                            placeholder="Filtrar por nombre..."
                            className="max-w-sm"
                            value={nameFilter}
                            onChange={(e) => setNameFilter(e.target.value)}
                        />
                        <Input
                            placeholder="Filtrar por correo..."
                            className="max-w-sm"
                            value={emailFilter}
                            onChange={(e) => setEmailFilter(e.target.value)}
                        />
                    </div>
                    <Button onClick={() => setIsAddUserOpen(true)}>Agregar Nuevo Usuario</Button>
                </div>
                <div className="relative flex-1 overflow-hidden rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>ID</TableHead>
                                <TableHead>Nombre</TableHead>
                                <TableHead>Username</TableHead>
                                <TableHead>Rol</TableHead>
                                <TableHead>Correo</TableHead>
                                <TableHead>NIR Empresa</TableHead>
                                <TableHead>Acciones</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {filteredUsers.map((user) => (
                                <TableRow key={user.id}>
                                    <TableCell>{user.id}</TableCell>
                                    <TableCell>{user.nombre}</TableCell>
                                    <TableCell>{user.username}</TableCell>
                                    <TableCell>{user.rol}</TableCell>
                                    <TableCell>{user.correo}</TableCell>
                                    <TableCell>{user.nir_empresa}</TableCell>
                                    <TableCell className="space-x-2">
                                        <Button variant={user.habilitado ? 'secondary' : 'default'}>
                                            {user.habilitado ? 'Deshabilitar' : 'Habilitar'}
                                        </Button>
                                        <Button variant="outline">Editar</Button>
                                        <Button variant="destructive">Eliminar</Button>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
            </div>
        </AppLayout>
    );
}