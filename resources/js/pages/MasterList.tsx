import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Settings } from 'lucide-react';
import { ConfigureProgramsDialog } from '@/components/configure-programs-dialog';


const breadcrumbs: BreadcrumbItem[] = [{ title: 'Listado Maestro', href: '/listado-maestro' }];

// --- DUMMY DATA FOR THE ENTIRE ECOSYSTEM ---
const allCompanies = [
    { id: 1, nombre: 'Tech Solutions S.A.', id_empresa: 'TS-001' },
    { id: 2, nombre: 'Innovate Corp', id_empresa: 'IC-002' },
    { id: 3, nombre: 'Global Logistics', id_empresa: 'GL-003' },
];

const allPrograms = [
    { id: 1, nombre: 'Gestión de Calidad Alimentaria', codigo: 'P-GCA-001', tipo: 'ISO 22000' },
    { id: 2, nombre: 'Buenas Prácticas de Saneamiento', codigo: 'P-BPS-002', tipo: 'PSB' },
    { id: 3, nombre: 'Control de Etiquetado y Empaque', codigo: 'P-CEE-003', tipo: 'Invima' },
];

const allAnnexes = [
    { id: 101, programId: 1, nombre: 'Registro de Temperaturas', codigo_anexo: 'A-RT-01' },
    { id: 102, programId: 1, nombre: 'Checklist de Limpieza', codigo_anexo: 'A-CL-02' },
    { id: 201, programId: 2, nombre: 'Plan de Fumigación', codigo_anexo: 'A-PF-03' },
    { id: 301, programId: 3, nombre: 'Verificación de Lote', codigo_anexo: 'A-VL-01' },
];

const initialMasterConfig = {
    1: { 1: [101, 102], 2: [201] },
    2: { 1: [101] },
};
// --- END OF DUMMY DATA ---


export default function MasterList() {
    const [masterConfig, setMasterConfig] = useState(initialMasterConfig);
    const [isDialogOpen, setIsDialogOpen] = useState(false);
    const [selectedCompany, setSelectedCompany] = useState(null);
    
    // --- NEW STATES FOR FILTERS ---
    const [nameFilter, setNameFilter] = useState('');
    const [idFilter, setIdFilter] = useState('');

    const handleOpenDialog = (company) => {
        setSelectedCompany(company);
        setIsDialogOpen(true);
    };

    const handleUpdateConfig = (companyId, newConfig) => {
        setMasterConfig(prev => ({
            ...prev,
            [companyId]: newConfig,
        }));
    };

    // --- FILTERING LOGIC ---
    const filteredCompanies = allCompanies.filter(
        company =>
            company.nombre.toLowerCase().includes(nameFilter.toLowerCase()) &&
            company.id_empresa.toLowerCase().includes(idFilter.toLowerCase())
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Listado Maestro" />

            {selectedCompany && (
                 <ConfigureProgramsDialog
                    isOpen={isDialogOpen}
                    onClose={() => setIsDialogOpen(false)}
                    company={selectedCompany}
                    companyConfig={masterConfig[selectedCompany.id] || {}}
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