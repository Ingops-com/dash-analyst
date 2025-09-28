import AppLayout from '@/layouts/app-layout'
import { type BreadcrumbItem } from '@/types'
import { Head } from '@inertiajs/react'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Progress } from '@/components/ui/progress'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Building, Users, Library, FileText, Activity, Image as ImageIcon, CheckCircle2, XCircle } from 'lucide-react'
import { Badge } from '@/components/ui/badge'

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/dashboard' }]

// === Tipos consistentes con ProgramView ===
export type AnnexType = 'IMAGES' | 'PDF' | 'WORD' | 'XLSX' | 'FORMATO'
interface Poe { id: number; date: string }
interface Annex { id: number; name: string; type: AnnexType; files: File[] | { name: string }[] } // en dashboard basta el conteo
interface Program { id: number; companyId: number; name: string; annexes: Annex[]; poes: Poe[] }
interface Company { id: number; nombre: string; status: 'activa' | 'inactiva'; logos: (string | null)[]; fechaRegistro: string }

// === Mock coherente con Companies + ProgramView ===
const companies: Company[] = [
  { id: 1, nombre: 'Tech Solutions S.A.', status: 'activa', logos: ['/images/logo_der.png','/images/logo_izq.png','/images/logo_footer.png'], fechaRegistro: '2024-08-01' },
  { id: 2, nombre: 'Innovate Corp', status: 'activa', logos: ['/images/logo_der.png', null, null], fechaRegistro: '2024-07-15' },
  { id: 3, nombre: 'Global Logistics', status: 'inactiva', logos: ['/images/logo_der.png','/images/logo_izq.png','/images/logo_footer.png'], fechaRegistro: '2024-06-20' },
]

const programs: Program[] = [
  { id: 101, companyId: 1, name: 'Limpieza y Desinfección', annexes: [
    { id: 1, name: 'Certificado de Fumigación', type: 'PDF', files: [{name:'cert-fumigacion.pdf'}] },
    { id: 2, name: 'Factura de Insumos', type: 'XLSX', files: [] },
    { id: 3, name: 'Registro Fotográfico', type: 'IMAGES', files: [{name:'1.jpg'},{name:'2.jpg'}] },
    { id: 4, name: 'Checklist Interno', type: 'FORMATO', files: [] },
  ], poes: [{ id: 1, date: '2025-09-10' }] },

  { id: 102, companyId: 2, name: 'Control de Plagas', annexes: [
    { id: 1, name: 'Acta de Visita', type: 'PDF', files: [] },
    { id: 2, name: 'Evidencias', type: 'IMAGES', files: [] },
    { id: 3, name: 'Planilla', type: 'FORMATO', files: [] },
  ], poes: [] },

  { id: 103, companyId: 3, name: 'Buenas Prácticas', annexes: [
    { id: 1, name: 'Capacitaciones', type: 'WORD', files: [{name:'capacitacion.docx'}] },
    { id: 2, name: 'Listado Personal', type: 'XLSX', files: [{name:'personal.xlsx'}] },
  ], poes: [{ id: 2, date: '2025-08-01' }, { id: 3, date: '2025-08-15' }] },
]

// === Helpers ===
const annexCounts = (annexes: Annex[]) => {
  // Excluye FORMATO para progreso, como en ProgramView
  const consider = annexes.filter(a => a.type !== 'FORMATO')
  const total = consider.length
  const completed = consider.filter(a => (a.files || []).length > 0).length
  return { total, completed, percentage: total ? Math.round((completed/total)*100) : 0 }
}

const totalAnnexDistribution = (() => {
  const dist: Record<AnnexType, number> = { IMAGES: 0, PDF: 0, WORD: 0, XLSX: 0, FORMATO: 0 }
  programs.forEach(p => p.annexes.forEach(a => { dist[a.type] = (dist[a.type]||0) + 1 }))
  return dist
})()

const totalPoes = programs.reduce((acc, p) => acc + p.poes.length, 0)

// Reusable Stat Card
const StatCard = ({ title, value, icon: Icon, description }: { title: string; value: number | string; icon: any; description?: string }) => (
  <Card>
    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
      <CardTitle className="text-sm font-medium">{title}</CardTitle>
      <Icon className="h-4 w-4 text-muted-foreground" />
    </CardHeader>
    <CardContent>
      <div className="text-2xl font-bold">{value}</div>
      {description && <p className="text-xs text-muted-foreground">{description}</p>}
    </CardContent>
  </Card>
)

export default function Dashboard() {
  const totalCompanies = companies.length
  const activeCompanies = companies.filter(c => c.status === 'activa').length
  const programsCount = programs.length

  // Anexos (excluye FORMATO al contar completados)
  const allAnnexes = programs.flatMap(p => p.annexes)
  const annexesForProgress = allAnnexes.filter(a => a.type !== 'FORMATO')
  const annexCompleted = annexesForProgress.filter(a => (a.files || []).length > 0).length
  const annexTotal = annexesForProgress.length

  // Branding completeness (3 logos fijos)
  const brandingReady = companies.filter(c => (c.logos.filter(Boolean).length === 3)).length

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Dashboard" />
      <div className="flex-1 space-y-4 p-4 pt-6 md:p-8">
        {/* Top Row: Quick Stats alineadas */}
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
          <StatCard title="Empresas Activas" value={`${activeCompanies}/${totalCompanies}`} icon={Building} description="Empresas registradas" />
          <StatCard title="Programas Activos" value={programsCount} icon={Library} description="Programas por empresa" />
          <StatCard title="Anexos Completados" value={`${annexCompleted}/${annexTotal}`} icon={FileText} description="No incluye FORMATO ni POES" />
          <StatCard title="POES Registrados" value={totalPoes} icon={Activity} description="No afectan el porcentaje" />
        </div>

        <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
          {/* Progreso por Programa */}
          <Card className="lg:col-span-2">
            <CardHeader>
              <CardTitle>Progreso por Programa</CardTitle>
            </CardHeader>
            <CardContent>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Empresa</TableHead>
                    <TableHead>Programa</TableHead>
                    <TableHead>Completados</TableHead>
                    <TableHead className="w-[140px]">Progreso</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {programs.map(p => {
                    const company = companies.find(c => c.id === p.companyId)!
                    const { total, completed, percentage } = annexCounts(p.annexes)
                    return (
                      <TableRow key={p.id}>
                        <TableCell className="font-medium">{company?.nombre}</TableCell>
                        <TableCell className="text-muted-foreground">{p.name}</TableCell>
                        <TableCell>{completed}/{total}</TableCell>
                        <TableCell>
                          <div className="flex items-center gap-2">
                            <Progress value={percentage} className="h-2" />
                            <span className="text-xs font-semibold">{percentage}%</span>
                          </div>
                        </TableCell>
                      </TableRow>
                    )
                  })}
                </TableBody>
              </Table>
            </CardContent>
          </Card>

          {/* Lateral: Estado del sistema */}
          <div className="space-y-4">
            {/* Branding */}
            <Card>
              <CardHeader>
                <CardTitle>Estado de Branding</CardTitle>
                <CardDescription>Logos requeridos: derecho, izquierdo y pie.</CardDescription>
              </CardHeader>
              <CardContent className="space-y-2">
                {companies.map(c => {
                  const filled = c.logos.filter(Boolean).length
                  const ok = filled === 3
                  return (
                    <div key={c.id} className="flex items-center justify-between text-sm py-1">
                      <div className="flex items-center gap-2">
                        {ok ? <CheckCircle2 className="h-4 w-4 text-emerald-600"/> : <XCircle className="h-4 w-4 text-amber-600"/>}
                        <span className="font-medium">{c.nombre}</span>
                      </div>
                      <span className="text-muted-foreground">{filled}/3</span>
                    </div>
                  )
                })}
                <div className="mt-2 text-xs text-muted-foreground">Completas: {brandingReady}/{companies.length}</div>
              </CardContent>
            </Card>

            {/* Distribución de anexos por tipo */}
            <Card>
              <CardHeader>
                <CardTitle>Distribución de Anexos</CardTitle>
                <CardDescription>Conteo por tipo (incluye FORMATO).</CardDescription>
              </CardHeader>
              <CardContent className="space-y-2">
                {(['IMAGES','PDF','WORD','XLSX','FORMATO'] as AnnexType[]).map(t => (
                  <div key={t} className="flex items-center justify-between text-sm">
                    <div className="flex items-center gap-2">
                      {t==='IMAGES' && <ImageIcon className="h-4 w-4 text-muted-foreground"/>}
                      {t==='PDF' && <FileText className="h-4 w-4 text-muted-foreground"/>}
                      {t==='WORD' && <FileText className="h-4 w-4 text-muted-foreground"/>}
                      {t==='XLSX' && <Activity className="h-4 w-4 text-muted-foreground"/>}
                      {t==='FORMATO' && <FileText className="h-4 w-4 text-muted-foreground"/>}
                      <span className="text-muted-foreground">{t}</span>
                    </div>
                    <span className="font-semibold">{totalAnnexDistribution[t]}</span>
                  </div>
                ))}
              </CardContent>
            </Card>

            {/* Actividad reciente */}
            <Card>
              <CardHeader>
                <CardTitle>Actividad Reciente</CardTitle>
                <CardDescription>Altas más recientes.</CardDescription>
              </CardHeader>
              <CardContent>
                {companies.slice(0, 3).map(company => (
                  <div key={company.id} className="flex items-center justify-between py-2 border-b last:border-0 text-sm">
                    <div className="flex items-center gap-2">
                      <Badge variant={company.status==='activa' ? 'secondary' : 'outline'}>{company.status}</Badge>
                      <span className="font-medium">{company.nombre}</span>
                    </div>
                    <span className="text-muted-foreground text-xs">{company.fechaRegistro}</span>
                  </div>
                ))}
              </CardContent>
            </Card>
          </div>
        </div>
      </div>
    </AppLayout>
  )
}
