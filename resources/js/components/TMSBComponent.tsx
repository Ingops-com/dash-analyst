import React, { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogTrigger, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { CheckCircle2, AlertCircle, FileText } from 'lucide-react';
import TMSBForm from './TMSBForm';

interface TMSBComponentProps {
    companyId: number;
    programId: number;
    programName?: string;
}

export default function TMSBComponent({ companyId, programId, programName = 'TMSB' }: TMSBComponentProps) {
    const [tmsbData, setTmsbData] = useState<any>(null);
    const [isLoading, setIsLoading] = useState(false);
    const [hasData, setHasData] = useState(false);
    const [isOpen, setIsOpen] = useState(false);

    // Cargar datos TMSB existentes
    useEffect(() => {
        fetchTMSBData();
    }, [companyId, programId]);

    const fetchTMSBData = async () => {
        setIsLoading(true);
        try {
            const response = await fetch(`/tmsb/company/${companyId}/program/${programId}`);
            const result = await response.json();
            
            if (result.success && result.data) {
                setTmsbData(result.data);
                setHasData(true);
            } else {
                setTmsbData(null);
                setHasData(false);
            }
        } catch (error) {
            console.error('Error al cargar datos TMSB:', error);
            setTmsbData(null);
            setHasData(false);
        } finally {
            setIsLoading(false);
        }
    };

    const handleSave = () => {
        setIsOpen(false);
        fetchTMSBData();
    };

    return (
        <Card className="border-blue-200 bg-blue-50">
            <CardHeader>
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <FileText className="h-5 w-5 text-blue-600" />
                        <CardTitle>Monitoreo de Saneamiento Básico (TMSB)</CardTitle>
                    </div>
                    {hasData && (
                        <div className="flex items-center gap-1 text-sm text-green-600">
                            <CheckCircle2 className="h-4 w-4" />
                            Completado
                        </div>
                    )}
                </div>
            </CardHeader>
            <CardContent className="space-y-4">
                {!hasData && (
                    <div className="flex items-start gap-3 rounded-lg bg-yellow-50 p-3 text-sm text-yellow-800">
                        <AlertCircle className="h-4 w-4 mt-0.5 flex-shrink-0" />
                        <p>No hay datos TMSB registrados. Haz clic en "Abrir Formulario" para comenzar.</p>
                    </div>
                )}

                {hasData && (
                    <div className="space-y-3 rounded-lg bg-white p-3">
                        <h4 className="font-semibold text-sm">Información registrada:</h4>
                        <div className="text-sm text-muted-foreground space-y-1">
                            <p>
                                <strong>Operadores:</strong>{' '}
                                {tmsbData.data_last?.[0]?.operators ? Object.keys(tmsbData.data_last[0].operators).length : 0}
                            </p>
                            <p>
                                <strong>Acciones Correctivas:</strong>{' '}
                                {tmsbData.correctiveActions?.length || 0}
                            </p>
                            <p>
                                <strong>Fecha de actualización:</strong>{' '}
                                {new Date(tmsbData.created_at || Date.now()).toLocaleDateString('es-CO')}
                            </p>
                        </div>
                    </div>
                )}

                <div className="flex gap-2">
                    <Dialog open={isOpen} onOpenChange={setIsOpen}>
                        <DialogTrigger asChild>
                            <Button variant="default" size="sm">
                                {hasData ? 'Editar Formulario' : 'Abrir Formulario'}
                            </Button>
                        </DialogTrigger>
                        <DialogContent className="max-w-6xl max-h-[90vh] overflow-y-auto">
                            <DialogHeader>
                                <DialogTitle>Monitoreo de Saneamiento Básico - {programName}</DialogTitle>
                            </DialogHeader>
                            <TMSBForm
                                companyId={companyId}
                                programId={programId}
                                initialData={tmsbData}
                                onSave={handleSave}
                                onCancel={() => setIsOpen(false)}
                            />
                        </DialogContent>
                    </Dialog>

                    {hasData && (
                        <>
                            <Button variant="outline" size="sm" disabled>
                                Descargar Excel
                            </Button>
                            <Button variant="outline" size="sm" disabled>
                                Descargar PDF
                            </Button>
                        </>
                    )}
                </div>
            </CardContent>
        </Card>
    );
}
