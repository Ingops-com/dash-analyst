import { useState } from 'react';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { FileDown, FileText, ChevronDown, ChevronUp } from 'lucide-react';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Documentos de Empresas',
        href: '/documentos-empresas',
    },
];

// Dummy data for companies
const allCompanies = [
    { id: 1, logo: 'https://via.placeholder.com/40', nombre: 'Tech Solutions S.A.', correo: 'contacto@techsolutions.com', nit_empresa: '900.123.456-7' },
    { id: 2, logo: 'https://via.placeholder.com/40', nombre: 'Innovate Corp', correo: 'info@innovate.com', nit_empresa: '800.789.123-4' },
    { id: 3, logo: 'https://via.placeholder.com/40', nombre: 'Global Logistics', correo: 'support@globallogistics.com', nit_empresa: '901.234.567-8' },
];

// Dummy data for company programs
const companyProgramsData = {
    1: [ // Programs for Tech Solutions
        { id: 101, programa: 'Plan de Gestión de Calidad (PGC)', status: 'Completado' },
        { id: 102, programa: 'Manual de Identidad Corporativa', status: 'En Progreso' },
    ],
    2: [ // Programs for Innovate Corp
        { id: 201, programa: 'Política de Seguridad de la Información', status: 'Completado' },
        { id: 202, programa: 'Protocolo de Respuesta a Incidentes', status: 'Pendiente' },
    ],
    3: [], // Global Logistics has no programs
};

const CompanyPrograms = ({ companyId }) => {
    const programs = companyProgramsData[companyId] || [];

    return (
        <div className="bg-muted/50 p-4">
            <Card>
                <CardHeader>
                    <CardTitle className="text-lg">Programas Asignados</CardTitle>
                    <CardDescription>Estos son los documentos y programas asociados a esta empresa.</CardDescription>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Nombre del Programa</TableHead>
                                <TableHead>Estado</TableHead>
                                <TableHead className="text-right">Acciones</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {programs.length > 0 ? (
                                programs.map((prog) => (
                                    <TableRow key={prog.id}>
                                        <TableCell className="font-medium">{prog.programa}</TableCell>
                                        <TableCell>
                                             <Badge
                                                variant={
                                                    prog.status === 'Completado' ? 'default' :
                                                    prog.status === 'En Progreso' ? 'outline' : 'destructive'
                                                }
                                            >
                                                {prog.status}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-right space-x-2">
                                            <Button variant="outline" size="sm">
                                                <FileText className="mr-2 h-4 w-4" /> Ver PDF
                                            </Button>
                                            <Button variant="outline" size="sm">
                                                <FileDown className="mr-2 h-4 w-4" /> Ver Word
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))
                            ) : (
                                <TableRow>
                                    <TableCell colSpan={3} className="text-center text-muted-foreground py-8">
                                        No hay programas asignados a esta empresa.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>
    );
};

export default function CompanyDocuments() {
    const [nameFilter, setNameFilter] = useState('');
    const [emailFilter, setEmailFilter] = useState('');
    const [nitFilter, setNitFilter] = useState('');
    const [openCompanyId, setOpenCompanyId] = useState<number | null>(null);

    const filteredCompanies = allCompanies.filter(
        (company) =>
            company.nombre.toLowerCase().includes(nameFilter.toLowerCase()) &&
            company.correo.toLowerCase().includes(emailFilter.toLowerCase()) &&
            company.nit_empresa.toLowerCase().includes(nitFilter.toLowerCase())
    );

    const toggleCompanyPrograms = (companyId: number) => {
        setOpenCompanyId(openCompanyId === companyId ? null : companyId);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Documentos de Empresas" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex justify-between items-center mb-4">
                    <div className="flex gap-2">
                        <Input placeholder="Filtrar por nombre..." className="max-w-sm" value={nameFilter} onChange={(e) => setNameFilter(e.target.value)} />
                        <Input placeholder="Filtrar por correo..." className="max-w-sm" value={emailFilter} onChange={(e) => setEmailFilter(e.target.value)} />
                        <Input placeholder="Filtrar por NIT..." className="max-w-sm" value={nitFilter} onChange={(e) => setNitFilter(e.target.value)} />
                    </div>
                </div>

                <div className="relative flex-1 overflow-hidden rounded-xl border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Logo</TableHead>
                                <TableHead>Nombre</TableHead>
                                <TableHead>Correo</TableHead>
                                <TableHead>NIT Empresa</TableHead>
                                <TableHead className="w-[180px]">Acciones</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {filteredCompanies.map((company) => (
                                <Collapsible asChild key={company.id} open={openCompanyId === company.id} onOpenChange={() => toggleCompanyPrograms(company.id)}>
                                    <>
                                        <TableRow>
                                            <TableCell>
                                                <Avatar>
                                                    <AvatarImage src={company.logo} alt={company.nombre} />
                                                    <AvatarFallback>{company.nombre.substring(0, 2)}</AvatarFallback>
                                                </Avatar>
                                            </TableCell>
                                            <TableCell className="font-medium">{company.nombre}</TableCell>
                                            <TableCell>{company.correo}</TableCell>
                                            <TableCell>{company.nit_empresa}</TableCell>
                                            <TableCell>
                                                <CollapsibleTrigger asChild>
                                                     <Button variant="ghost" size="sm">
                                                        {openCompanyId === company.id ? <ChevronUp className="mr-2 h-4 w-4" /> : <ChevronDown className="mr-2 h-4 w-4" />}
                                                        Ver Programas
                                                    </Button>
                                                </CollapsibleTrigger>
                                            </TableCell>
                                        </TableRow>
                                        <TableRow className="p-0">
                                            <TableCell colSpan={5} className="p-0 border-0">
                                                <CollapsibleContent>
                                                    <CompanyPrograms companyId={company.id} />
                                                </CollapsibleContent>
                                            </TableCell>
                                        </TableRow>
                                    </>
                                </Collapsible>
                            ))}
                        </TableBody>
                    </Table>
                </div>
            </div>
        </AppLayout>
    );
}