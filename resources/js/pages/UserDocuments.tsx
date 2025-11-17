import { useMemo, useState } from 'react';
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
import { Head, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { FileDown, FileText, ChevronDown, ChevronUp } from 'lucide-react';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Documentos de Empresas',
        href: '/documentos-empresas',
    },
];

type ProgramItem = { id: number; nombre: string; codigo?: string; has_pdf: boolean; has_docx: boolean; pdf_url?: string | null; docx_url?: string | null }
type CompanyItem = { id: number; nombre: string; correo?: string; nit_empresa?: string; logo?: string | null }
type Item = { company: CompanyItem; programs: ProgramItem[] }

const CompanyPrograms = ({ programs }: { programs: ProgramItem[] }) => {
    const { props } = usePage();
    const userRole = ((props as any).auth?.user?.rol || '').toLowerCase();
    const canDownload = userRole === 'admin' || userRole === 'super-admin';

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
                                        <TableCell className="font-medium">{prog.nombre}</TableCell>
                                        <TableCell className="space-x-2">
                                            <Badge variant={prog.has_pdf ? 'default' : 'outline'}>{prog.has_pdf ? 'PDF' : 'Sin PDF'}</Badge>
                                            <Badge variant={prog.has_docx ? 'default' : 'outline'}>{prog.has_docx ? 'DOCX' : 'Sin DOCX'}</Badge>
                                        </TableCell>
                                        <TableCell className="text-right space-x-2">
                                            <TooltipProvider>
                                                {prog.pdf_url ? (
                                                    canDownload ? (
                                                        <a href={prog.pdf_url} target="_blank" rel="noreferrer">
                                                            <Button variant="outline" size="sm">
                                                                <FileText className="mr-2 h-4 w-4" /> Ver PDF
                                                            </Button>
                                                        </a>
                                                    ) : (
                                                        <Tooltip>
                                                            <TooltipTrigger asChild>
                                                                <span>
                                                                    <Button variant="outline" size="sm" disabled>
                                                                        <FileText className="mr-2 h-4 w-4" /> Ver PDF
                                                                    </Button>
                                                                </span>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                <p>Solo administradores y analistas pueden descargar documentos</p>
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    )
                                                ) : (
                                                    <Button variant="outline" size="sm" disabled>
                                                        <FileText className="mr-2 h-4 w-4" /> PDF no disponible
                                                    </Button>
                                                )}
                                                {prog.docx_url ? (
                                                    canDownload ? (
                                                        <a href={prog.docx_url} target="_blank" rel="noreferrer">
                                                            <Button variant="outline" size="sm">
                                                                <FileDown className="mr-2 h-4 w-4" /> Ver Word
                                                            </Button>
                                                        </a>
                                                    ) : (
                                                        <Tooltip>
                                                            <TooltipTrigger asChild>
                                                                <span>
                                                                    <Button variant="outline" size="sm" disabled>
                                                                        <FileDown className="mr-2 h-4 w-4" /> Ver Word
                                                                    </Button>
                                                                </span>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                <p>Solo administradores y analistas pueden descargar documentos</p>
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    )
                                                ) : (
                                                    <Button variant="outline" size="sm" disabled>
                                                        <FileDown className="mr-2 h-4 w-4" /> Word no disponible
                                                    </Button>
                                                )}
                                            </TooltipProvider>
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

export default function CompanyDocuments({ items }: { items: Item[] }) {
    const [nameFilter, setNameFilter] = useState('');
    const [emailFilter, setEmailFilter] = useState('');
    const [nitFilter, setNitFilter] = useState('');
    const [openCompanyId, setOpenCompanyId] = useState<number | null>(null);

    const filtered = useMemo(() => {
        return (items || []).filter(({ company }) =>
            (company.nombre || '').toLowerCase().includes(nameFilter.toLowerCase()) &&
            (company.correo || '').toLowerCase().includes(emailFilter.toLowerCase()) &&
            (company.nit_empresa || '').toLowerCase().includes(nitFilter.toLowerCase())
        );
    }, [items, nameFilter, emailFilter, nitFilter]);

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
                            {filtered.map(({ company, programs }) => (
                                <Collapsible asChild key={company.id} open={openCompanyId === company.id} onOpenChange={() => toggleCompanyPrograms(company.id)}>
                                    <>
                                        <TableRow>
                                            <TableCell>
                                                <Avatar>
                                                    <AvatarImage src={company.logo || undefined} alt={company.nombre} />
                                                    <AvatarFallback>{(company.nombre || 'NA').substring(0, 2)}</AvatarFallback>
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
                                                    <CompanyPrograms programs={programs} />
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