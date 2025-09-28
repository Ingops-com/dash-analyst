import { useState, useMemo } from 'react';
import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Eye, Building, Calendar, Phone, Hash, User, Briefcase } from 'lucide-react';
import { Progress } from '@/components/ui/progress';

// Tipos de datos simulados
interface Program {
    id: number;
    code: string;
    name: string;
    progress: number;
}
interface Company {
    id: number;
    name: string;
    nit: string;
    representative: string;
    startDate: string;
    endDate: string;
    version: string;
    phone: string;
    address: string;
    activities: string;
    logos: string[];
    programs: Program[];
}

// Datos de ejemplo
const companiesData: Company[] = [
    {
        id: 1,
        name: 'Empresa A',
        nit: '123.456.789-0',
        representative: 'Juan Pérez',
        startDate: '2023-01-15',
        endDate: '2025-01-15',
        version: '1.0',
        phone: '3101234567',
        address: 'Calle Falsa 123',
        activities: 'Desarrollo de software',
        logos: ['/images/logo.png', '/images/logo.png', '/images/logo.png'],
        programs: [
            { id: 1, code: 'P001', name: 'Programa 1', progress: 75 },
            { id: 2, code: 'P002', name: 'Programa 2', progress: 50 },
            { id: 3, code: 'P003', name: 'Programa 3', progress: 25 },
            { id: 4, code: 'P004', name: 'Programa 4', progress: 90 },
        ],
    },
    {
        id: 2,
        name: 'Empresa B',
        nit: '987.654.321-0',
        representative: 'Ana Gómez',
        startDate: '2022-05-20',
        endDate: '2024-05-20',
        version: '2.1',
        phone: '3209876543',
        address: 'Avenida Siempre Viva 742',
        activities: 'Consultoría TI',
        logos: ['/images/logo.png', '/images/logo.png', '/images/logo.png'],
        programs: [
            { id: 5, code: 'PX01', name: 'Programa X', progress: 90 },
            { id: 6, code: 'PY02', name: 'Programa Y', progress: 40 },
        ],
    },
];

export default function Companies() {
    const [selectedCompany, setSelectedCompany] = useState<Company | null>(null);
    const [nameFilter, setNameFilter] = useState('');
    const [nitFilter, setNitFilter] = useState('');

    const handleViewCompany = (company: Company) => {
        setSelectedCompany(prev => (prev && prev.id === company.id ? null : company));
    };

    const filteredCompanies = useMemo(() => {
        return companiesData.filter(company =>
            company.name.toLowerCase().includes(nameFilter.toLowerCase()) &&
            company.nit.toLowerCase().includes(nitFilter.toLowerCase())
        );
    }, [nameFilter, nitFilter]);

    return (
        <AppLayout>
            <Head title="Empresas" />

            <div className='flex h-full flex-1 flex-col gap-4 p-4'>
                <CardDescription>
                    Busca empresas por nombre o NIT.
                </CardDescription>

                <div className="flex space-x-4 py-4">
                    <Input
                        placeholder="Filtrar por nombre..."
                        value={nameFilter}
                        onChange={(e) => setNameFilter(e.target.value)}
                        className="max-w-sm"
                    />
                    <Input
                        placeholder="Filtrar por NIT..."
                        value={nitFilter}
                        onChange={(e) => setNitFilter(e.target.value)}
                        className="max-w-sm"
                    />
                </div>

                <div className="border rounded-lg">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Nombre</TableHead>
                                <TableHead>NIT</TableHead>
                                <TableHead>Representante</TableHead>
                                <TableHead className="text-right">Acciones</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {filteredCompanies.map((company) => (
                                <>
                                    <TableRow key={company.id} onClick={() => handleViewCompany(company)} className="cursor-pointer">
                                        <TableCell>{company.name}</TableCell>
                                        <TableCell>{company.nit}</TableCell>
                                        <TableCell>{company.representative}</TableCell>
                                        <TableCell className="text-right">
                                            <Button variant="outline" size="icon">
                                                <Eye className="h-4 w-4" />
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                    {selectedCompany && selectedCompany.id === company.id && (
                                        <TableRow>
                                            <TableCell colSpan={4}>
                                                <div className="grid grid-cols-1 lg:grid-cols-5 gap-6 p-4">
                                                    {/* Columna de Información de la Empresa */}
                                                    <Card className="lg:col-span-3">
                                                        <CardHeader>
                                                            <CardTitle>Información de la Empresa</CardTitle>
                                                        </CardHeader>
                                                        <CardContent className="space-y-6">
                                                            <div className="flex justify-center space-x-4 py-4">
                                                                {selectedCompany.logos.map((logo, index) => (
                                                                    <img key={index} src={logo} alt={`Logo ${index + 1}`} className="h-24 w-24 object-contain border rounded-lg p-2 shadow-sm" />
                                                                ))}
                                                            </div>
                                                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                                                                <div className="flex items-center gap-2"><Building className="h-4 w-4 text-muted-foreground" /><strong>Dirección:</strong> {selectedCompany.address}</div>
                                                                <div className="flex items-center gap-2"><User className="h-4 w-4 text-muted-foreground" /><strong>Representante:</strong> {selectedCompany.representative}</div>
                                                                <div className="flex items-center gap-2"><Hash className="h-4 w-4 text-muted-foreground" /><strong>NIT:</strong> {selectedCompany.nit}</div>
                                                                <div className="flex items-center gap-2"><Phone className="h-4 w-4 text-muted-foreground" /><strong>Teléfono:</strong> {selectedCompany.phone}</div>
                                                                <div className="flex items-center gap-2"><Calendar className="h-4 w-4 text-muted-foreground" /><strong>Fecha Inicio:</strong> {selectedCompany.startDate}</div>
                                                                <div className="flex items-center gap-2"><Calendar className="h-4 w-4 text-muted-foreground" /><strong>Fecha Fin:</strong> {selectedCompany.endDate}</div>
                                                                <div className="flex items-center gap-2"><Briefcase className="h-4 w-4 text-muted-foreground" /><strong>Actividades:</strong> {selectedCompany.activities}</div>
                                                                <div className="flex items-center gap-2"><Hash className="h-4 w-4 text-muted-foreground" /><strong>Versión:</strong> {selectedCompany.version}</div>
                                                            </div>
                                                        </CardContent>
                                                    </Card>

                                                    {/* Columna de Programas Asignados */}
                                                    <Card className="lg:col-span-2">
                                                        <CardHeader>
                                                            <CardTitle>Programas Asignados</CardTitle>
                                                        </CardHeader>
                                                        <CardContent className="space-y-4 max-h-[400px] overflow-y-auto">
                                                            {selectedCompany.programs.map((program) => (
                                                                <Card key={program.id}>
                                                                    <CardContent className="p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                                                                        <div className="flex-1 w-full">
                                                                            <p className="font-bold">{program.code} - {program.name}</p>
                                                                            <div className="flex items-center gap-2 mt-2">
                                                                                <Progress value={program.progress} className="w-full" />
                                                                                <span className="text-sm font-medium">{program.progress}%</span>
                                                                            </div>
                                                                        </div>
                                                                        <Button variant="default" size="sm" className="mt-2 sm:mt-0">
                                                                            Ir al Programa
                                                                        </Button>
                                                                    </CardContent>
                                                                </Card>
                                                            ))}
                                                        </CardContent>
                                                    </Card>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </>
                            ))}
                        </TableBody>
                    </Table>
                </div>
            </div>
        </AppLayout>
    );
}