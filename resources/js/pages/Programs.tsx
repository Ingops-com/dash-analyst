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
    annexes: Annex[];
};

type Props = {
    programs: Program[];
};

// Component Definition

const AnnexCard = ({ anexo }: { anexo: Annex }) => (
    <Card className="bg-muted/40">
        <CardHeader className="py-3 px-4">
            <CardTitle className="text-sm font-semibold">{anexo.nombre}</CardTitle>
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
    const [programs, setPrograms] = useState<Program[]>(serverPrograms);

    const filteredPrograms = programs.filter((p: Program) => 
        p.nombre.toLowerCase().includes(nameFilter.toLowerCase()) && 
        p.codigo.toLowerCase().includes(codeFilter.toLowerCase())
    );

    // --- HANDLERS ---
    const handleSaveProgram = (data: Omit<Program, 'id' | 'color' | 'annexes'>) => {
        setPrograms([...programs, { 
            ...data, 
            id: Math.max(0, ...programs.map(p => p.id)) + 1,
            color: 'bg-gray-500',
            annexes: []
        }]);
    };

    const handleSaveAnnex = (data: Omit<Annex, 'id'>) => {
        console.log('New Annex:', data);
        /* Add logic to create annex and link it */
    };

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
                                                <div><CardTitle>{program.nombre}</CardTitle><CardDescription>{program.codigo} &bull; V{program.version}</CardDescription></div>
                                                <Badge variant="secondary">{program.tipo}</Badge>
                                            </div>
                                        </CardHeader>
                                        <CardContent>
                                            <Separator className="mb-4" />
                                            <h4 className="text-sm font-semibold mb-3 text-muted-foreground">Anexos Vinculados</h4>
                                            {program.annexes.length > 0 ? (
                                                <Collapsible>
                                                    <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                                                        {visibleAnnexes.map((anexo: Annex) => <AnnexCard key={anexo.id} anexo={anexo} />)}
                                                    </div>
                                                    {hiddenAnnexes.length > 0 && (
                                                        <>
                                                            <CollapsibleContent className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 mt-4">
                                                                {hiddenAnnexes.map((anexo: Annex) => <AnnexCard key={anexo.id} anexo={anexo} />)}
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