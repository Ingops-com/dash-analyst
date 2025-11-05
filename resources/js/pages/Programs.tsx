import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { PlusCircle, FilePlus, ChevronDown, FileText, AlertCircle, Pencil } from 'lucide-react';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { AddProgramDialog } from '@/components/add-program-dialog';
import { AddAnnexDialog } from '@/components/add-annex-dialog';


const breadcrumbs: BreadcrumbItem[] = [{ title: 'Programas', href: '/programas' }];

// Type definitions

// Dummy data...
// Types moved to top for clarity
type Annex = {
    id: number;
    nombre: string;
    codigo_anexo: string;
    tipo: string;
    consecutivo: number;
    programId: number;
};

type Program = {
    id: number;
    nombre: string;
    version: string;
    codigo: string;
    fecha: string;
    tipo: string;
    color: string;
    template_path?: string;
    description?: string;
    annexes: Annex[];
};

type Props = {
    programs: Program[];
};

// Component Definition

const AnnexCard = ({ anexo, onEdit }: { anexo: Annex; onEdit: (anexo: Annex) => void }) => (
    <Card className="bg-muted/40">
        <CardHeader className="py-3 px-4">
            <div className="flex justify-between items-start">
                <CardTitle className="text-sm font-semibold">{anexo.nombre}</CardTitle>
                <Button 
                    variant="ghost" 
                    size="sm" 
                    className="h-6 w-6 p-0"
                    onClick={() => onEdit(anexo)}
                >
                    <Pencil className="h-3 w-3" />
                </Button>
            </div>
            <CardDescription className="text-xs">{anexo.codigo_anexo}</CardDescription>
        </CardHeader>
        <CardFooter className="py-2 px-4 flex justify-between text-xs text-muted-foreground">
            <span>{anexo.tipo}</span>
            <span>Consecutivo: #{anexo.consecutivo}</span>
        </CardFooter>
    </Card>
);

export default function Programs({ programs: serverPrograms }: Props) {
    const [nameFilter, setNameFilter] = useState('');
    const [codeFilter, setCodeFilter] = useState('');
    const [isAddProgramOpen, setIsAddProgramOpen] = useState(false);
    const [isAddAnnexOpen, setIsAddAnnexOpen] = useState(false);
    const [editingProgram, setEditingProgram] = useState<Program | null>(null);
    const [editingAnnex, setEditingAnnex] = useState<Annex | null>(null);

    const filteredPrograms = serverPrograms.filter((p: Program) => 
        p.nombre.toLowerCase().includes(nameFilter.toLowerCase()) && 
        p.codigo.toLowerCase().includes(codeFilter.toLowerCase())
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Programas" />
                <AddProgramDialog 
                    isOpen={isAddProgramOpen || editingProgram !== null} 
                    onClose={() => { setIsAddProgramOpen(false); setEditingProgram(null); }}
                    program={editingProgram}
                />
                <AddAnnexDialog 
                    isOpen={isAddAnnexOpen || editingAnnex !== null} 
                    onClose={() => { setIsAddAnnexOpen(false); setEditingAnnex(null); }}
                    programs={serverPrograms}
                    annex={editingAnnex ? {
                        ...editingAnnex,
                        programIds: serverPrograms
                            .filter(p => p.annexes.some(a => a.id === editingAnnex.id))
                            .map(p => p.id)
                    } : null}
                />

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
                    {filteredPrograms.map((program: Program) => {
                        const visibleAnnexes = program.annexes.slice(0, 3);
                        const hiddenAnnexes = program.annexes.slice(3);

                        return (
                            <Card key={program.id} className="overflow-hidden">
                                <div className="flex">
                                    <div className={`w-2 ${program.color}`} />
                                    <div className="flex-1">
                                        <CardHeader>
                                            <div className="flex justify-between items-start">
                                                <div>
                                                    <CardTitle>{program.nombre}</CardTitle>
                                                    <CardDescription>{program.codigo} &bull; V{program.version}</CardDescription>
                                                    {program.description && (
                                                        <p className="text-sm text-muted-foreground mt-2 max-w-2xl">
                                                            {program.description}
                                                        </p>
                                                    )}
                                                </div>
                                                <div className="flex flex-col gap-2 items-end">
                                                    <Badge variant="secondary">{program.tipo}</Badge>
                                                    <div className="flex items-center gap-2">
                                                        <Button 
                                                            variant="ghost" 
                                                            size="sm" 
                                                            className="h-7 px-2"
                                                            onClick={() => setEditingProgram(program)}
                                                        >
                                                            <Pencil className="h-3.5 w-3.5 mr-1" /> Editar
                                                        </Button>
                                                    </div>
                                                    {program.template_path ? (
                                                        <Badge variant="outline" className="text-green-600 border-green-600">
                                                            <FileText className="mr-1 h-3 w-3" />
                                                            Plantilla configurada
                                                        </Badge>
                                                    ) : (
                                                        <Badge variant="outline" className="text-amber-600 border-amber-600">
                                                            <AlertCircle className="mr-1 h-3 w-3" />
                                                            Sin plantilla
                                                        </Badge>
                                                    )}
                                                </div>
                                            </div>
                                            {program.template_path && (
                                                <p className="text-xs text-muted-foreground mt-2 font-mono bg-muted px-2 py-1 rounded">
                                                    📄 {program.template_path}
                                                </p>
                                            )}
                                        </CardHeader>
                                        <CardContent>
                                            <Separator className="mb-4" />
                                            <h4 className="text-sm font-semibold mb-3 text-muted-foreground">Anexos Vinculados</h4>
                                            {program.annexes.length > 0 ? (
                                                <Collapsible>
                                                    <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                                                 {visibleAnnexes.map((anexo: Annex) => <AnnexCard key={anexo.id} anexo={anexo} onEdit={setEditingAnnex} />)}
                                                    </div>
                                                    {hiddenAnnexes.length > 0 && (
                                                        <>
                                                            <CollapsibleContent className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 mt-4">
                                                        {hiddenAnnexes.map((anexo: Annex) => <AnnexCard key={anexo.id} anexo={anexo} onEdit={setEditingAnnex} />)}
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