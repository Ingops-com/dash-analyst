import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Building, Users, Library, FileText, Activity } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/dashboard' }];

// --- DUMMY DATA AGGREGATED FROM OTHER VIEWS ---
const allCompanies = [
    { id: 1, nombre: 'Tech Solutions S.A.', fechaRegistro: '2024-08-01' },
    { id: 2, nombre: 'Innovate Corp', fechaRegistro: '2024-07-15' },
    { id: 3, nombre: 'Global Logistics', fechaRegistro: '2024-06-20' },
];

const allUsers = [ { role: 'Administrador' }, { role: 'Analista' }, { role: 'Analista' } ];
const allPrograms = [ { tipo: 'ISO 22000' }, { tipo: 'PSB' }, { tipo: 'Invima' }, { tipo: 'ISO 22000' } ];
const allAnnexes = [ {}, {}, {}, {}, {} ];

// Mock implementation progress data
const implementationProgress = {
    1: { assigned: 8, completed: 7 },  // Tech Solutions
    2: { assigned: 5, completed: 2 },  // Innovate Corp
    3: { assigned: 10, completed: 10 }, // Global Logistics
};
// --- END OF DUMMY DATA ---

// Reusable Stat Card Component
const StatCard = ({ title, value, icon: Icon, description }) => (
    <Card>
        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">{title}</CardTitle>
            <Icon className="h-4 w-4 text-muted-foreground" />
        </CardHeader>
        <CardContent>
            <div className="text-2xl font-bold">{value}</div>
            <p className="text-xs text-muted-foreground">{description}</p>
        </CardContent>
    </Card>
);

export default function Dashboard() {
    // Calculate stats from data
    const totalCompanies = allCompanies.length;
    const totalUsers = allUsers.length;
    const totalPrograms = allPrograms.length;
    const totalAnnexes = allAnnexes.length;

    const programTypesCount = allPrograms.reduce((acc, program) => {
        acc[program.tipo] = (acc[program.tipo] || 0) + 1;
        return acc;
    }, {});

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex-1 space-y-4 p-4 pt-6 md:p-8">
                {/* Top Row: Quick Stats */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <StatCard title="Empresas Activas" value={totalCompanies} icon={Building} description="Total de empresas registradas" />
                    <StatCard title="Usuarios del Sistema" value={totalUsers} icon={Users} description={`${allUsers.filter(u => u.role === 'Analista').length} Analistas`} />
                    <StatCard title="Programas Creados" value={totalPrograms} icon={Library} description="Total de programas maestros" />
                    <StatCard title="Anexos Totales" value={totalAnnexes} icon={FileText} description="Documentos vinculados a programas" />
                </div>

                <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
                    {/* Center Column: Implementation Progress */}
                    <Card className="lg:col-span-2">
                        <CardHeader>
                            <CardTitle>Progreso de Implementación por Empresa</CardTitle>
                            <CardDescription>
                                Porcentaje de programas completados por cada empresa.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Empresa</TableHead>
                                        <TableHead>Programas</TableHead>
                                        <TableHead className="w-[120px]">Progreso</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {allCompanies.map(company => {
                                        const progress = implementationProgress[company.id];
                                        const percentage = progress ? Math.round((progress.completed / progress.assigned) * 100) : 0;
                                        return (
                                            <TableRow key={company.id}>
                                                <TableCell className="font-medium">{company.nombre}</TableCell>
                                                <TableCell className="text-muted-foreground">{progress ? `${progress.completed} / ${progress.assigned}` : 'N/A'}</TableCell>
                                                <TableCell>
                                                    <div className="flex items-center gap-2">
                                                        <Progress value={percentage} className="h-2" />
                                                        <span className="text-xs font-semibold">{percentage}%</span>
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        );
                                    })}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>

                    {/* Right Column: System Overview */}
                    <div className="space-y-4">
                        <Card>
                            <CardHeader>
                                <CardTitle>Distribución de Programas</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-2">
                                {Object.entries(programTypesCount).map(([tipo, count]) => (
                                    <div key={tipo} className="flex justify-between text-sm">
                                        <span className="text-muted-foreground">{tipo}</span>
                                        <span className="font-semibold">{count}</span>
                                    </div>
                                ))}
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader>
                                <CardTitle>Actividad Reciente</CardTitle>
                                <CardDescription>Últimas empresas registradas.</CardDescription>
                            </CardHeader>
                            <CardContent>
                                {allCompanies.slice(0, 3).map(company => (
                                    <div key={company.id} className="flex items-center justify-between py-2 border-b last:border-0">
                                        <p className="text-sm font-medium">{company.nombre}</p>
                                        <p className="text-xs text-muted-foreground">{company.fechaRegistro}</p>
                                    </div>
                                ))}
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}