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
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { AddCompanyDialog } from '@/components/add-company-dialog';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Lista Empresas',
        href: '/lista-empresas',
    },
];

// Dummy data for companies
const initialCompanies = [
    {
        id: 1,
        logo: 'https://via.placeholder.com/40',
        nombre: 'Tech Solutions S.A.',
        correo: 'contacto@techsolutions.com',
        nit_empresa: '900.123.456-7',
        habilitado: true,
        // ... other fields
    },
    {
        id: 2,
        logo: 'https://via.placeholder.com/40',
        nombre: 'Innovate Corp',
        correo: 'info@innovate.com',
        nit_empresa: '800.789.123-4',
        habilitado: false,
        // ... other fields
    },
];

export default function Companies() {
    const [companies, setCompanies] = useState(initialCompanies);
    const [nameFilter, setNameFilter] = useState('');
    const [emailFilter, setEmailFilter] = useState('');
    const [nitFilter, setNitFilter] = useState('');
    const [isAddCompanyOpen, setIsAddCompanyOpen] = useState(false);
    const [editingCompany, setEditingCompany] = useState(null);

    const filteredCompanies = companies.filter(
        (company) =>
            company.nombre.toLowerCase().includes(nameFilter.toLowerCase()) &&
            company.correo.toLowerCase().includes(emailFilter.toLowerCase()) &&
            company.nit_empresa.toLowerCase().includes(nitFilter.toLowerCase())
    );

    const handleOpenCreate = () => {
        setEditingCompany(null);
        setIsAddCompanyOpen(true);
    };

    const handleOpenEdit = (company) => {
        setEditingCompany(company);
        setIsAddCompanyOpen(true);
    };

    const handleCloseDialog = () => {
        setIsAddCompanyOpen(false);
        setEditingCompany(null);
    };

    const handleSaveCompany = (companyData) => {
        if (editingCompany) {
            setCompanies(companies.map((c) => (c.id === editingCompany.id ? { ...c, ...companyData } : c)));
        } else {
            setCompanies([...companies, { ...companyData, id: companies.length + 1, habilitado: true }]);
        }
        handleCloseDialog();
    };


    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Empresas" />
             <AddCompanyDialog
                isOpen={isAddCompanyOpen}
                onClose={handleCloseDialog}
                onSaveCompany={handleSaveCompany}
                companyToEdit={editingCompany}
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
                         <Input
                            placeholder="Filtrar por NIT..."
                            className="max-w-sm"
                            value={nitFilter}
                            onChange={(e) => setNitFilter(e.target.value)}
                        />
                    </div>
                    <Button onClick={handleOpenCreate}>Agregar Nueva Empresa</Button>
                </div>
                <div className="relative flex-1 overflow-hidden rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>ID</TableHead>
                                <TableHead>Logo</TableHead>
                                <TableHead>Nombre</TableHead>
                                <TableHead>Correo</TableHead>
                                <TableHead>NIT Empresa</TableHead>
                                <TableHead>Acciones</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {filteredCompanies.map((company) => (
                                <TableRow key={company.id}>
                                    <TableCell>{company.id}</TableCell>
                                    <TableCell>
                                        <Avatar>
                                            <AvatarImage src={company.logo} alt={company.nombre} />
                                            <AvatarFallback>{company.nombre.substring(0, 2)}</AvatarFallback>
                                        </Avatar>
                                    </TableCell>
                                    <TableCell>{company.nombre}</TableCell>
                                    <TableCell>{company.correo}</TableCell>
                                    <TableCell>{company.nit_empresa}</TableCell>
                                    <TableCell className="space-x-2">
                                        <Button variant={company.habilitado ? 'secondary' : 'default'}>
                                            {company.habilitado ? 'Deshabilitar' : 'Habilitar'}
                                        </Button>
                                        <Button variant="outline" onClick={() => handleOpenEdit(company)}>Editar</Button>
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