import { useState, useEffect } from 'react';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from './ui/card';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from './ui/collapsible';
import { ChevronDown, PlusCircle, Unlink } from 'lucide-react';

// Main component to manage the program configuration dialog
export function ConfigureProgramsDialog({ isOpen, onClose, company, companyConfig, allPrograms, allAnnexes, onConfigUpdate }) {
    // Local state to manage changes without affecting the parent state until "Save" is clicked
    const [config, setConfig] = useState(companyConfig);

    // When the dialog is opened or the company changes, reset the local state
    useEffect(() => {
        setConfig(companyConfig);
    }, [companyConfig, isOpen]);

    // Guard clause in case the dialog is rendered without a selected company
    if (!company) return null;

    // Derived state: calculate which programs are assigned and which are available
    const assignedProgramIds = Object.keys(config).map(Number);
    const assignedPrograms = allPrograms.filter(p => assignedProgramIds.includes(p.id));
    const availablePrograms = allPrograms.filter(p => !assignedProgramIds.includes(p.id));

    // Handler to toggle an annex's enabled/disabled state for a specific program
    const handleAnnexToggle = (programId, annexId) => {
        const currentEnabledAnnexes = config[programId] || [];
        const isAnnexEnabled = currentEnabledAnnexes.includes(annexId);

        const newEnabledAnnexes = isAnnexEnabled
            ? currentEnabledAnnexes.filter(id => id !== annexId) // Disable: remove from array
            : [...currentEnabledAnnexes, annexId]; // Enable: add to array

        setConfig(prev => ({ ...prev, [programId]: newEnabledAnnexes }));
    };

    // Handler to assign a new program to the company
    const handleAssignProgram = (programId) => {
        // By default, a new program is assigned with ALL its annexes enabled
        const annexesForProgram = allAnnexes.filter(a => a.programId === programId).map(a => a.id);
        setConfig(prev => ({ ...prev, [programId]: annexesForProgram }));
    };

    // Handler to unassign (or unlink) a program from the company
    const handleUnassignProgram = (programId) => {
        // We create a new object from the previous state and delete the key for the program
        const newConfig = { ...config };
        delete newConfig[programId];
        setConfig(newConfig);
    };

    // Handler to save all changes and close the dialog
    const handleSaveChanges = () => {
        onConfigUpdate(company.id, config);
        onClose();
    };

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="sm:max-w-4xl h-[90vh] flex flex-col">
                <DialogHeader>
                    <DialogTitle>Configurar Programas para: {company.nombre}</DialogTitle>
                    <DialogDescription>
                        Asigna programas y habilita los anexos correspondientes para esta empresa.
                    </DialogDescription>
                </DialogHeader>

                <Tabs defaultValue="assigned" className="flex-1 overflow-hidden flex flex-col">
                    <TabsList className="grid w-full grid-cols-2">
                        <TabsTrigger value="assigned">Programas Asignados ({assignedPrograms.length})</TabsTrigger>
                        <TabsTrigger value="available">Asignar Nuevos Programas ({availablePrograms.length})</TabsTrigger>
                    </TabsList>
                    
                    {/* Assigned Programs Tab */}
                    <TabsContent value="assigned" className="flex-1 overflow-y-auto p-4 space-y-4">
                        {assignedPrograms.length > 0 ? assignedPrograms.map(program => {
                            const programAnnexes = allAnnexes.filter(a => a.programId === program.id);
                            return (
                                <Card key={program.id}>
                                    <CardHeader>
                                        <div className="flex justify-between items-center">
                                            <CardTitle className="text-lg">{program.nombre}</CardTitle>
                                            <Button variant="ghost" size="sm" onClick={() => handleUnassignProgram(program.id)}>
                                                <Unlink className="mr-2 h-4 w-4" /> Desvincular
                                            </Button>
                                        </div>
                                        <CardDescription>{program.codigo} &bull; {program.tipo}</CardDescription>
                                    </CardHeader>
                                    <CardContent>
                                        <Collapsible>
                                            <CollapsibleTrigger asChild>
                                                <Button variant="outline" size="sm" className="w-full" disabled={programAnnexes.length === 0}>
                                                    <ChevronDown className="mr-2 h-4 w-4" /> 
                                                    {programAnnexes.length > 0 
                                                        ? `Configurar Anexos (${config[program.id]?.length || 0} / ${programAnnexes.length} habilitados)`
                                                        : 'Este programa no tiene anexos'
                                                    }
                                                </Button>
                                            </CollapsibleTrigger>
                                            <CollapsibleContent className="mt-4 space-y-3 px-2">
                                                {programAnnexes.map(annex => (
                                                    <div key={annex.id} className="flex items-center justify-between p-2 rounded-md border">
                                                        <div>
                                                            <Label htmlFor={`annex-${program.id}-${annex.id}`} className="cursor-pointer">{annex.nombre}</Label>
                                                            <p className="text-xs text-muted-foreground">{annex.codigo_anexo}</p>
                                                        </div>
                                                        <Switch
                                                            id={`annex-${program.id}-${annex.id}`}
                                                            checked={config[program.id]?.includes(annex.id) || false}
                                                            onCheckedChange={() => handleAnnexToggle(program.id, annex.id)}
                                                        />
                                                    </div>
                                                ))}
                                            </CollapsibleContent>
                                        </Collapsible>
                                    </CardContent>
                                </Card>
                            );
                        }) : <p className="text-center text-muted-foreground py-10">Esta empresa no tiene programas asignados.</p>}
                    </TabsContent>

                    {/* Available Programs Tab */}
                     <TabsContent value="available" className="flex-1 overflow-y-auto p-4 space-y-3">
                        {availablePrograms.length > 0 ? availablePrograms.map(program => (
                            <div key={program.id} className="flex items-center justify-between p-3 border rounded-lg">
                                <div>
                                    <p className="font-semibold">{program.nombre}</p>
                                    <p className="text-sm text-muted-foreground">{program.codigo}</p>
                                </div>
                                <Button size="sm" onClick={() => handleAssignProgram(program.id)}>
                                    <PlusCircle className="mr-2 h-4 w-4" /> Asignar
                                </Button>
                            </div>
                        )) : <p className="text-center text-muted-foreground py-10">Todos los programas del sistema ya están asignados.</p>}
                    </TabsContent>
                </Tabs>

                 <div className="pt-4 border-t">
                    <Button onClick={handleSaveChanges} className="w-full">Guardar Cambios</Button>
                </div>
            </DialogContent>
        </Dialog>
    );
}