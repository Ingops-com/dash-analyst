import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Settings } from 'lucide-react';
import { ConfigureProgramsDialog } from '@/components/configure-programs-dialog';


const breadcrumbs: BreadcrumbItem[] = [{ title: 'Listado Maestro', href: '/listado-maestro' }];

// Data comes from server via Inertia props
type PageProps = {
    companies: Array<{ id: number; nombre: string; id_empresa: string }>
    programs: Array<{ id: number; nombre: string; codigo: string; tipo: string }>
    annexes: Array<{ id: number; nombre: string; codigo_anexo: string; programId: number }>
    masterConfig: Record<string, Record<string, number[]>>
}


export default function MasterList() {
        const { props } = usePage<PageProps>() as any
        const allCompanies = (props?.companies ?? []) as PageProps['companies']
        const allPrograms = (props?.programs ?? []) as PageProps['programs']
        const allAnnexes = (props?.annexes ?? []) as PageProps['annexes']
        const initialMasterConfig = (props?.masterConfig ?? {}) as PageProps['masterConfig']

        const [masterConfig, setMasterConfig] = useState<Record<string, any>>(initialMasterConfig);
    const [isDialogOpen, setIsDialogOpen] = useState(false);
    const [selectedCompany, setSelectedCompany] = useState<null | { id: number; nombre: string; id_empresa: string }>(null);
    
    // --- NEW STATES FOR FILTERS ---
    const [nameFilter, setNameFilter] = useState('');
    const [idFilter, setIdFilter] = useState('');

    const handleOpenDialog = (company: { id: number; nombre: string; id_empresa: string }) => {
        setSelectedCompany(company);
        setIsDialogOpen(true);
    };

    const handleUpdateConfig = (companyId: number, newConfig: Record<string, number[]>) => {
        // Update UI optimistically
        setMasterConfig(prev => ({ ...prev, [companyId]: newConfig }));
        // Persist to backend
        router.post('/listado-maestro/config', {
            company_id: companyId,
            config: newConfig,
        }, { preserveScroll: true });
    };

    // --- FILTERING LOGIC ---
    const filteredCompanies = allCompanies.filter(
        company =>
            company.nombre.toLowerCase().includes(nameFilter.toLowerCase()) &&
            String(company.id_empresa ?? '').toLowerCase().includes(idFilter.toLowerCase())
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Listado Maestro" />

            {selectedCompany && (
                 <ConfigureProgramsDialog
                    isOpen={isDialogOpen}
                    onClose={() => setIsDialogOpen(false)}
                    company={selectedCompany}
                    companyConfig={masterConfig[String(selectedCompany.id)] || {}}
                    allPrograms={allPrograms}
                    allAnnexes={allAnnexes}
                    onConfigUpdate={handleUpdateConfig}
                />
            )}

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                {/* --- FILTER INPUTS ADDED HERE --- */}
                <div className="flex justify-between items-center mb-4">
                    <div className="flex gap-2">
                        <Input
                            placeholder="Filtrar por nombre de empresa..."
                            className="max-w-sm"
                            value={nameFilter}
                            onChange={(e) => setNameFilter(e.target.value)}
                        />
                        <Input
                            placeholder="Filtrar por ID de empresa..."
                            className="max-w-sm"
                            value={idFilter}
                            onChange={(e) => setIdFilter(e.target.value)}
                        />
                    </div>
                </div>

                <div className="relative flex-1 overflow-hidden rounded-xl border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Nombre de la Empresa</TableHead>
                                <TableHead>ID Empresa</TableHead>
                                <TableHead>Programas Asignados</TableHead>
                                <TableHead className="text-right">Opciones</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {/* --- Use filteredCompanies instead of allCompanies --- */}
                            {filteredCompanies.map((company) => {
                                const companyPrograms = masterConfig[company.id] ? Object.keys(masterConfig[company.id]).length : 0;
                                return (
                                    <TableRow key={company.id}>
                                        <TableCell className="font-medium">{company.nombre}</TableCell>
                                        <TableCell>{company.id_empresa}</TableCell>
                                        <TableCell>{companyPrograms}</TableCell>
                                        <TableCell className="text-right">
                                            <Button variant="outline" size="sm" onClick={() => handleOpenDialog(company)}>
                                                <Settings className="mr-2 h-4 w-4" />
                                                Configurar Programas
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                );
                            })}
                        </TableBody>
                    </Table>
                </div>
            </div>
        </AppLayout>
    );
}