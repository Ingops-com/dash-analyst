import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { PlusCircle, FilePlus, ChevronDown } from 'lucide-react';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { AddProgramDialog } from '@/components/add-program-dialog';
import { AddAnnexDialog } from '@/components/add-annex-dialog';


const breadcrumbs: BreadcrumbItem[] = [{ title: 'Programas', href: '/programas' }];

// Dummy data...
const initialPrograms = [
    { id: 1, nombre: 'Gestión de Calidad Alimentaria', version: '2.1', codigo: 'P-GCA-001', fecha: '2024-08-15', tipo: 'ISO 22000', color: 'bg-blue-500' },
    { id: 2, nombre: 'Buenas Prácticas de Saneamiento', version: '1.5', codigo: 'P-BPS-002', fecha: '2024-07-20', tipo: 'PSB', color: 'bg-green-500' },
    { id: 3, nombre: 'Control de Etiquetado y Empaque', version: '1.0', codigo: 'P-CEE-003', fecha: '2024-09-01', tipo: 'Invima', color: 'bg-red-500' },
];

const initialAnnexes = [
    { id: 101, nombre: 'Registro de Temperaturas', codigo_anexo: 'A-RT-01', tipo: 'ISO 22000', consecutivo: 1, programId: 1 },
    { id: 102, nombre: 'Checklist de Limpieza', codigo_anexo: 'A-CL-02', tipo: 'PSB', consecutivo: 2, programId: 1 },
    { id: 201, nombre: 'Plan de Fumigación', codigo_anexo: 'A-PF-03', tipo: 'Invima', consecutivo: 1, programId: 2 },
    { id: 103, nombre: 'Formato de No Conformidad', codigo_anexo: 'A-FNC-04', tipo: 'ISO 22000', consecutivo: 3, programId: 1 },
];

// Annex Card Component
const AnnexCard = ({ anexo }) => (
    <Card className="bg-muted/40">
        <CardHeader className="py-3 px-4">
            <CardTitle className="text-sm font-semibold">{anexo.nombre}</CardTitle>
            <CardDescription className="text-xs">{anexo.codigo_anexo}</CardDescription>
        </CardHeader>
        <CardFooter className="py-2 px-4 flex justify-between text-xs text-muted-foreground">
            {/* --- TEXT CHANGED HERE --- */}
            <span>{anexo.tipo}</span>
            <span>Consecutivo: #{anexo.consecutivo}</span>
        </CardFooter>
    </Card>
);

export default function Programs() {
    const [programs, setPrograms] = useState(initialPrograms);
    const [annexes, setAnnexes] = useState(initialAnnexes);
    const [nameFilter, setNameFilter] = useState('');
    const [codeFilter, setCodeFilter] = useState('');
    const [isAddProgramOpen, setIsAddProgramOpen] = useState(false);
    const [isAddAnnexOpen, setIsAddAnnexOpen] = useState(false);

    const filteredPrograms = programs.filter(p => p.nombre.toLowerCase().includes(nameFilter.toLowerCase()) && p.codigo.toLowerCase().includes(codeFilter.toLowerCase()));

    // Handlers for saving data from dialogs (updates dummy data state)
    const handleSaveProgram = (data) => { setPrograms([...programs, { ...data, id: programs.length + 1, color: 'bg-gray-500' }]); };
    const handleSaveAnnex = (data) => { console.log('New Annex:', data); /* Add logic to create annex and link it */ };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Programas" />
            <AddProgramDialog isOpen={isAddProgramOpen} onClose={() => setIsAddProgramOpen(false)} onSave={handleSaveProgram} />
            <AddAnnexDialog isOpen={isAddAnnexOpen} onClose={() => setIsAddAnnexOpen(false)} onSave={handleSaveAnnex} programs={programs} />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap justify-between items-center gap-4 mb-4">
                    <div className="flex gap-2">
                        <Input placeholder="Filtrar por nombre..." className="max-w-xs" value={nameFilter} onChange={(e) => setNameFilter(e.target.value)} />
                        <Input placeholder="Filtrar por código..." className="max-w-xs" value={codeFilter} onChange={(e) => setCodeFilter(e.target.value)} />
                    </div>
                    <div className="flex gap-2">
                        <Button variant="outline" onClick={() => setIsAddAnnexOpen(true)}><FilePlus className="mr-2 h-4 w-4" />Agregar Anexo</Button>
                        <Button onClick={() => setIsAddProgramOpen(true)}><PlusCircle className="mr-2 h-4 w-4" />Agregar Programa</Button>
                    </div>
                </div>

                <div className="space-y-6">
                    {filteredPrograms.map((program) => {
                        const programAnnexes = annexes.filter(anexo => anexo.programId === program.id);
                        const visibleAnnexes = programAnnexes.slice(0, 5);
                        const hiddenAnnexes = programAnnexes.slice(5);

                        return (
                            <Card key={program.id} className="overflow-hidden">
                                <div className="flex">
                                    <div className={`w-2 ${program.color}`} />
                                    <div className="flex-1">
                                        <CardHeader>
                                            <div className="flex justify-between items-start">
                                                <div><CardTitle>{program.nombre}</CardTitle><CardDescription>{program.codigo} &bull; V{program.version}</CardDescription></div>
                                                <Badge variant="secondary">{program.tipo}</Badge>
                                            </div>
                                        </CardHeader>
                                        <CardContent>
                                            <Separator className="mb-4" />
                                            <h4 className="text-sm font-semibold mb-3 text-muted-foreground">Anexos Vinculados</h4>
                                            {programAnnexes.length > 0 ? (
                                                <Collapsible>
                                                    <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                                                        {visibleAnnexes.map(anexo => <AnnexCard key={anexo.id} anexo={anexo} />)}
                                                    </div>
                                                    {hiddenAnnexes.length > 0 && (
                                                        <>
                                                            <CollapsibleContent className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 mt-4">
                                                                {hiddenAnnexes.map(anexo => <AnnexCard key={anexo.id} anexo={anexo} />)}
                                                            </CollapsibleContent>
                                                            <div className="mt-4 flex justify-center">
                                                                <CollapsibleTrigger asChild>
                                                                    <Button variant="ghost" size="sm">
                                                                        <ChevronDown className="mr-2 h-4 w-4" />
                                                                        Mostrar {hiddenAnnexes.length} más
                                                                    </Button>
                                                                </CollapsibleTrigger>
                                                            </div>
                                                        </>
                                                    )}
                                                </Collapsible>
                                            ) : (
                                                <p className="text-sm text-center text-muted-foreground py-4">No tiene anexos asignados.</p>
                                            )}
                                        </CardContent>
                                    </div>
                                </div>
                            </Card>
                        );
                    })}
                </div>
            </div>
        </AppLayout>
    );
}