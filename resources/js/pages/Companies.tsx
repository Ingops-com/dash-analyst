import { useMemo, useState, useEffect } from 'react'
import AppLayout from '@/layouts/app-layout'
import { Head, Link, router, usePage } from '@inertiajs/react'

// UI (wrappers locales basados en Radix + Tailwind del proyecto)
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger, DialogFooter } from '@/components/ui/dialog'
import { Label } from '@/components/ui/label'
import { Badge } from '@/components/ui/badge'
import { Tabs, TabsList, TabsTrigger, TabsContent } from '@/components/ui/tabs'
import { Switch } from '@/components/ui/switch'
import { Progress } from '@/components/ui/progress'

// Íconos (lucide-react)
import { Eye, Building, Calendar, Phone, Hash, User, Briefcase, Pencil, X, Globe, AtSign, ShieldCheck, Loader2 } from 'lucide-react'

// --- TIPOS ---
interface Program { id: number; code: string; name: string; progress: number }
interface Company {
  id: number
  name: string
  nit: string
  representative: string
  startDate: string
  endDate: string
  version: string
  phone: string
  address: string
  activities: string
  logos: string[]
  programs: Program[]
  email?: string
  website?: string
  altPhone?: string
  city?: string
  country?: string
  industry?: string
  employeesRange?: string
  status?: 'activa' | 'inactiva'
  notes?: string
}

// --- DATA MOCK ---
const companiesData: Company[] = [
  {
    id: 1,
    name: 'Empresa A',
    nit: '123.456.789-0',
    representative: 'Juan Pérez',
    startDate: '2023-01-15',
    endDate: '2025-01-15',
    version: '1.0',
    phone: '3101234567',
    address: 'Calle Falsa 123',
    activities: 'Desarrollo de software',
    logos: ['/images/logo.png', '/images/logo.png', '/images/logo.png'],
    programs: [
      { id: 1, code: 'P001', name: 'Programa 1', progress: 75 },
      { id: 2, code: 'P002', name: 'Programa 2', progress: 50 },
    ],
    email: 'contacto@empresaa.com',
    website: 'https://empresaa.com',
    altPhone: '6011234567',
    city: 'Bogotá',
    country: 'Colombia',
    industry: 'Tecnología',
    employeesRange: '11-50',
    status: 'activa',
    notes: 'Cliente priorizado. Renovación en Q1.'
  },
  {
    id: 2,
    name: 'Empresa B',
    nit: '987.654.321-0',
    representative: 'Ana Gómez',
    startDate: '2022-05-20',
    endDate: '2024-05-20',
    version: '2.1',
    phone: '3209876543',
    address: 'Avenida Siempre Viva 742',
    activities: 'Consultoría TI',
    logos: ['/images/logo.png', '/images/logo.png', '/images/logo.png'],
    programs: [{ id: 5, code: 'PX01', name: 'Programa X', progress: 90 }],
    email: 'info@empresab.co',
    website: 'https://empresab.co',
    city: 'Medellín',
    country: 'Colombia',
    industry: 'Consultoría',
    employeesRange: '51-200',
    status: 'activa',
  },
]

// --- HELPERS ---
const nitClean = (nit: string) => nit.replace(/[^0-9kK]/g, '').toUpperCase()
function validateNIT(nitRaw: string) {
  const nit = nitClean(nitRaw)
  if (nit.length < 2) return false
  const body = nit.slice(0, -1)
  const ver = nit.slice(-1)
  const primes = [3,7,13,17,19,23,29,37,41,43,47,53,59,67,71]
  let sum = 0
  const digits = body.split('').reverse()
  for (let i = 0; i < digits.length; i++) sum += parseInt(digits[i]||'0',10) * (primes[i] || 0)
  const mod = sum % 11
  const check = mod > 1 ? 11 - mod : mod
  const checkChar = check === 10 ? 'K' : String(check)
  return checkChar === ver
}
const phoneMask = (v: string) => v.replace(/[^0-9]/g, '').slice(0, 10)

export default function Companies() {
  const [selectedCompany, setSelectedCompany] = useState<Company | null>(null)
  const [nameFilter, setNameFilter] = useState('')
  const [nitFilter, setNitFilter] = useState('')
  const [isEditDialogOpen, setIsEditDialogOpen] = useState(false)
  const [saving, setSaving] = useState(false)

  const [formData, setFormData] = useState<Partial<Company>>({})
  const [logoFiles, setLogoFiles] = useState<File[]>([])
  const [logoPreviews, setLogoPreviews] = useState<string[]>([])
  const [errors, setErrors] = useState<Record<string, string>>({})

  const handleViewCompany = (company: Company) => {
    setSelectedCompany(prev => (prev && prev.id === company.id ? null : company))
  }

  // Leer props enviados por Inertia (si existen) y usar mocks como fallback
  const { props } = usePage()
  const serverProps = (props ?? {}) as any
  const companies = (serverProps.companies ?? companiesData) as Company[]

  const filteredCompanies = useMemo(() => {
    return companies.filter((company) =>
      company.name.toLowerCase().includes(nameFilter.toLowerCase()) &&
      company.nit.toLowerCase().includes(nitFilter.toLowerCase())
    )
  }, [nameFilter, nitFilter, companies])

  useEffect(() => {
    if (!isEditDialogOpen || !selectedCompany) return
    setFormData({ ...selectedCompany })
    setLogoPreviews(selectedCompany.logos || [])
    setLogoFiles([])
    setErrors({})
  }, [isEditDialogOpen, selectedCompany])

  const updateField = (key: keyof Company, value: any) => setFormData(prev => ({ ...prev, [key]: value }))

  const handleLogoChange: React.ChangeEventHandler<HTMLInputElement> = (e) => {
    const files = e.target.files ? Array.from(e.target.files) : []
    setLogoFiles(files)
    setLogoPreviews(files.map(f => URL.createObjectURL(f)))
  }
  const removePreview = (i: number) => {
    setLogoPreviews(prev => prev.filter((_, idx) => idx !== i))
    setLogoFiles(prev => prev.filter((_, idx) => idx !== i))
  }

  const validate = () => {
    const e: Record<string, string> = {}
    if (!formData?.name || formData.name.trim().length < 2) e.name = 'Nombre muy corto'
    if (!formData?.nit || formData.nit.trim().length < 7) e.nit = 'NIT muy corto'
    else if (!/^[0-9.\-Kk]+$/.test(formData.nit)) e.nit = 'Formato de NIT inválido'
    else if (!validateNIT(formData.nit.replace(/\./g,'').replace(/\-/g,''))) e.nit = 'Dígito de verificación inválido'

    if (!formData?.representative || formData.representative.trim().length < 3) e.representative = 'Representante requerido'
    if (!formData?.phone || phoneMask(formData.phone).length < 7) e.phone = 'Teléfono inválido'
    if (!formData?.address || formData.address.trim().length < 3) e.address = 'Dirección requerida'

    if (!formData?.startDate) e.startDate = 'Fecha de inicio requerida'
    if (!formData?.endDate) e.endDate = 'Fecha de fin requerida'
    if (formData?.startDate && formData?.endDate && formData.startDate > formData.endDate) e.endDate = 'La fecha fin debe ser posterior a inicio'

    if (formData?.email && !/^\S+@\S+\.\S+$/.test(formData.email)) e.email = 'Correo inválido'
    if (formData?.website && !/^https?:\/\//i.test(formData.website)) e.website = 'URL debe iniciar con http(s)://'

    setErrors(e)
    return Object.keys(e).length === 0
  }

  const onSubmit = async () => {
    if (!selectedCompany) return
    if (!validate()) return
    try {
      setSaving(true)
      const payload: any = { ...formData }
      if (logoFiles.length) {
        const fd = new FormData()
        Object.entries(payload).forEach(([k, v]) => v != null && fd.append(k, String(v)))
        logoFiles.forEach((f, i) => fd.append(`logos[${i}]`, f))
        // router.post(`/companies/${selectedCompany.id}`, fd, { forceFormData: true })
      } else {
        // router.put(`/companies/${selectedCompany.id}`, payload)
      }
      await new Promise(r => setTimeout(r, 800))
      setIsEditDialogOpen(false)
    } finally {
      setSaving(false)
    }
  }

  return (
    <AppLayout>
      <Head title="Empresas" />

      <div className='flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4'>
      <div className="flex flex-col sm:flex-row gap-3 py-4">
        <Input placeholder="Filtrar por nombre..." value={nameFilter} onChange={(e) => setNameFilter(e.target.value)} className="max-w-sm" />
        <Input placeholder="Filtrar por NIT..." value={nitFilter} onChange={(e) => setNitFilter(e.target.value)} className="max-w-sm" />
      </div>

      <div className="border rounded-lg">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Nombre</TableHead>
              <TableHead>NIT</TableHead>
              <TableHead>Representante</TableHead>
              <TableHead className="text-right">Acciones</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {filteredCompanies.map((company) => (
              <>
                <TableRow key={company.id} onClick={() => handleViewCompany(company)} className="cursor-pointer hover:bg-muted/30">
                  <TableCell className="font-medium flex items-center gap-2">
                    <span className={`h-2.5 w-2.5 rounded-full ${company.status !== 'inactiva' ? 'bg-emerald-500' : 'bg-gray-400'}`}></span>
                    {company.name}
                  </TableCell>
                  <TableCell>{company.nit}</TableCell>
                  <TableCell>{company.representative}</TableCell>
                  <TableCell className="text-right">
                    <Button variant="outline" size="icon" onClick={(e) => { e.stopPropagation(); handleViewCompany(company) }}>
                      <Eye className="h-4 w-4" />
                    </Button>
                  </TableCell>
                </TableRow>

                {selectedCompany && selectedCompany.id === company.id && (
                  <TableRow>
                    <TableCell colSpan={4}>
                      <div className="grid grid-cols-1 lg:grid-cols-5 gap-6 p-4">
                        {/* Info Empresa */}
                        <Card className="lg:col-span-3">
                          <CardHeader className="flex flex-row items-center justify-between">
                            <div>
                              <CardTitle>Información de la Empresa</CardTitle>
                              <CardDescription className="mt-1 flex flex-wrap items-center gap-2">
                                <Badge variant="secondary" className="gap-1"><ShieldCheck className="h-3.5 w-3.5"/> Versión {company.version}</Badge>
                                {company.website && (
                                  <a href={company.website} target="_blank" rel="noreferrer" className="text-xs inline-flex items-center gap-1 text-primary hover:underline">
                                    <Globe className="h-3.5 w-3.5"/> Sitio web
                                  </a>
                                )}
                                {company.email && (
                                  <a href={`mailto:${company.email}`} className="text-xs inline-flex items-center gap-1 text-primary hover:underline">
                                    <AtSign className="h-3.5 w-3.5"/> {company.email}
                                  </a>
                                )}
                              </CardDescription>
                            </div>

                            <Dialog open={isEditDialogOpen} onOpenChange={setIsEditDialogOpen}>
                              <DialogTrigger asChild>
                                <Button variant="outline" size="sm">
                                  <Pencil className="h-4 w-4 mr-2" /> Editar
                                </Button>
                              </DialogTrigger>

                              <DialogContent className="sm:max-w-[900px] max-h-[85vh] overflow-y-auto">
                                <DialogHeader>
                                  <DialogTitle>Editar empresa</DialogTitle>
                                  <CardDescription>Actualiza datos generales, contacto, legal y branding.</CardDescription>
                                </DialogHeader>

                                <Tabs defaultValue="general" className="w-full">
                                  <TabsList className="grid grid-cols-2 sm:grid-cols-4 gap-2 w-full">
                                    <TabsTrigger value="general">General</TabsTrigger>
                                    <TabsTrigger value="contacto">Contacto</TabsTrigger>
                                    <TabsTrigger value="legal">Legal</TabsTrigger>
                                    <TabsTrigger value="branding">Branding</TabsTrigger>
                                  </TabsList>

                                  <TabsContent value="general" className="space-y-4 pt-4">
                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                      <div>
                                        <Label>Nombre</Label>
                                        <Input value={formData.name || ''} onChange={(e) => updateField('name', e.target.value)} placeholder="Razón social" />
                                        {errors.name && <p className="text-xs text-destructive mt-1">{errors.name}</p>}
                                      </div>
                                      <div>
                                        <Label>NIT</Label>
                                        <Input value={formData.nit || ''} onChange={(e) => updateField('nit', e.target.value)} placeholder="123.456.789-0" />
                                        {errors.nit && <p className="text-xs text-destructive mt-1">{errors.nit}</p>}
                                      </div>
                                      <div>
                                        <Label>Representante legal</Label>
                                        <Input value={formData.representative || ''} onChange={(e) => updateField('representative', e.target.value)} placeholder="Nombre completo" />
                                        {errors.representative && <p className="text-xs text-destructive mt-1">{errors.representative}</p>}
                                      </div>
                                      <div className="grid grid-cols-2 gap-4">
                                        <div>
                                          <Label>Fecha inicio</Label>
                                          <Input type="date" value={formData.startDate || ''} onChange={(e) => updateField('startDate', e.target.value)} />
                                          {errors.startDate && <p className="text-xs text-destructive mt-1">{errors.startDate}</p>}
                                        </div>
                                        <div>
                                          <Label>Fecha fin</Label>
                                          <Input type="date" value={formData.endDate || ''} onChange={(e) => updateField('endDate', e.target.value)} />
                                          {errors.endDate && <p className="text-xs text-destructive mt-1">{errors.endDate}</p>}
                                        </div>
                                      </div>
                                      <div>
                                        <Label>Versión</Label>
                                        <Input value={formData.version || ''} onChange={(e) => updateField('version', e.target.value)} placeholder="1.0" />
                                      </div>
                                      <div>
                                        <Label>Industria</Label>
                                        <Select value={formData.industry || ''} onValueChange={(v) => updateField('industry', v)}>
                                          <SelectTrigger><SelectValue placeholder="Selecciona una industria" /></SelectTrigger>
                                          <SelectContent>
                                            <SelectItem value="Tecnología">Tecnología</SelectItem>
                                            <SelectItem value="Consultoría">Consultoría</SelectItem>
                                            <SelectItem value="Manufactura">Manufactura</SelectItem>
                                            <SelectItem value="Salud">Salud</SelectItem>
                                            <SelectItem value="Educación">Educación</SelectItem>
                                            <SelectItem value="Otra">Otra</SelectItem>
                                          </SelectContent>
                                        </Select>
                                      </div>
                                      <div>
                                        <Label>Tamaño de empleados</Label>
                                        <Select value={formData.employeesRange || ''} onValueChange={(v) => updateField('employeesRange', v)}>
                                          <SelectTrigger><SelectValue placeholder="Rango" /></SelectTrigger>
                                          <SelectContent>
                                            <SelectItem value="1-10">1-10</SelectItem>
                                            <SelectItem value="11-50">11-50</SelectItem>
                                            <SelectItem value="51-200">51-200</SelectItem>
                                            <SelectItem value=">200">&gt;200</SelectItem>
                                          </SelectContent>
                                        </Select>
                                      </div>
                                      <div className="sm:col-span-2 border rounded-lg p-3 flex items-center justify-between">
                                        <div>
                                          <Label className="mb-0">Estado</Label>
                                          <p className="text-xs text-muted-foreground">Activa / Inactiva</p>
                                        </div>
                                        <div className="flex items-center gap-2">
                                          <Switch checked={(formData.status || 'activa') !== 'inactiva'} onCheckedChange={(c) => updateField('status', c ? 'activa' : 'inactiva')} />
                                          <Badge variant="outline">{(formData.status || 'activa') === 'inactiva' ? 'Inactiva' : 'Activa'}</Badge>
                                        </div>
                                      </div>
                                      <div className="sm:col-span-2">
                                        <Label>Actividades</Label>
                                        <Textarea rows={3} value={formData.activities || ''} onChange={(e) => updateField('activities', e.target.value)} placeholder="Describe las actividades principales" />
                                      </div>
                                    </div>
                                  </TabsContent>

                                  <TabsContent value="contacto" className="space-y-4 pt-4">
                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                      <div>
                                        <Label>Correo</Label>
                                        <Input value={formData.email || ''} onChange={(e) => updateField('email', e.target.value)} placeholder="correo@empresa.com" />
                                        {errors.email && <p className="text-xs text-destructive mt-1">{errors.email}</p>}
                                      </div>
                                      <div>
                                        <Label>Sitio web</Label>
                                        <Input value={formData.website || ''} onChange={(e) => updateField('website', e.target.value)} placeholder="https://" />
                                        {errors.website && <p className="text-xs text-destructive mt-1">{errors.website}</p>}
                                      </div>
                                      <div>
                                        <Label>Teléfono</Label>
                                        <Input value={formData.phone || ''} onChange={(e) => updateField('phone', phoneMask(e.target.value))} placeholder="310... / 601..." />
                                        {errors.phone && <p className="text-xs text-destructive mt-1">{errors.phone}</p>}
                                      </div>
                                      <div>
                                        <Label>Teléfono alterno (opcional)</Label>
                                        <Input value={formData.altPhone || ''} onChange={(e) => updateField('altPhone', phoneMask(e.target.value))} placeholder="Otro contacto" />
                                      </div>
                                      <div className="sm:col-span-2">
                                        <Label>Dirección</Label>
                                        <Input value={formData.address || ''} onChange={(e) => updateField('address', e.target.value)} placeholder="Dirección completa" />
                                        {errors.address && <p className="text-xs text-destructive mt-1">{errors.address}</p>}
                                      </div>
                                      <div>
                                        <Label>Ciudad</Label>
                                        <Input value={formData.city || ''} onChange={(e) => updateField('city', e.target.value)} placeholder="Ciudad" />
                                      </div>
                                      <div>
                                        <Label>País</Label>
                                        <Input value={formData.country || ''} onChange={(e) => updateField('country', e.target.value)} placeholder="País" />
                                      </div>
                                      <div className="sm:col-span-2">
                                        <Label>Notas internas</Label>
                                        <Textarea rows={3} value={formData.notes || ''} onChange={(e) => updateField('notes', e.target.value)} placeholder="Observaciones, acuerdos, recordatorios..." />
                                      </div>
                                    </div>
                                  </TabsContent>

                                  <TabsContent value="legal" className="space-y-4 pt-4">
                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                      <div>
                                        <Label>Documentos asociados (RUT, Cámara de comercio)</Label>
                                        <Input type="file" multiple className="mt-2" />
                                        <p className="text-xs text-muted-foreground mt-1">PDF/JPG/PNG. Máx 5MB c/u.</p>
                                      </div>
                                      <div>
                                        <Label>Fecha de última actualización documental</Label>
                                        <Input type="date" className="mt-2" defaultValue={formData.startDate || ''} />
                                      </div>
                                    </div>
                                  </TabsContent>

                                  <TabsContent value="branding" className="space-y-4 pt-4">
                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                      <div>
                                        <Label>Logos</Label>
                                        <Input id="logos" type="file" multiple className="mt-2" onChange={handleLogoChange} accept="image/*" />
                                        <p className="text-xs text-muted-foreground mt-1">Sube PNG/JPG con fondo transparente si es posible.</p>
                                      </div>
                                      <div>
                                        <Label>Color de marca (hex)</Label>
                                        <Input type="text" placeholder="#0ea5e9" />
                                      </div>
                                    </div>
                                    {logoPreviews.length > 0 && (
                                      <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                        {logoPreviews.map((src, i) => (
                                          <div key={i} className="relative group border rounded-lg p-2 bg-background">
                                            <button type="button" onClick={() => removePreview(i)} className="absolute -top-2 -right-2 bg-destructive text-destructive-foreground rounded-full p-1 opacity-0 group-hover:opacity-100 transition">
                                              <X className="h-3.5 w-3.5" />
                                            </button>
                                            <img src={src} className="h-24 w-full object-contain" alt={`logo-${i}`} />
                                          </div>
                                        ))}
                                      </div>
                                    )}
                                  </TabsContent>
                                </Tabs>

                                <DialogFooter className="gap-2 sm:gap-0">
                                  <Button type="button" variant="outline" onClick={() => setIsEditDialogOpen(false)}>Cancelar</Button>
                                  <Button type="button" onClick={onSubmit} disabled={saving}>
                                    {saving && <Loader2 className="mr-2 h-4 w-4 animate-spin"/>}
                                    Guardar cambios
                                  </Button>
                                </DialogFooter>
                              </DialogContent>
                            </Dialog>
                          </CardHeader>

                          <CardContent className="space-y-6">
                            <div className="flex justify-center flex-wrap gap-4 py-4">
                              {(selectedCompany.logos || []).map((logo, index) => (
                                <img key={index} src={logo} alt={`Logo ${index + 1}`} className="h-20 w-20 object-contain border rounded-lg p-2 shadow-sm" />
                              ))}
                            </div>
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                              <div className="flex items-center gap-2"><Building className="h-4 w-4 text-muted-foreground" /><strong>Dirección:</strong> {selectedCompany.address}</div>
                              <div className="flex items-center gap-2"><User className="h-4 w-4 text-muted-foreground" /><strong>Representante:</strong> {selectedCompany.representative}</div>
                              <div className="flex items-center gap-2"><Hash className="h-4 w-4 text-muted-foreground" /><strong>NIT:</strong> {selectedCompany.nit}</div>
                              <div className="flex items-center gap-2"><Phone className="h-4 w-4 text-muted-foreground" /><strong>Teléfono:</strong> {selectedCompany.phone}</div>
                              <div className="flex items-center gap-2"><Calendar className="h-4 w-4 text-muted-foreground" /><strong>Fecha Inicio:</strong> {selectedCompany.startDate}</div>
                              <div className="flex items-center gap-2"><Calendar className="h-4 w-4 text-muted-foreground" /><strong>Fecha Fin:</strong> {selectedCompany.endDate}</div>
                              <div className="flex items-center gap-2"><Briefcase className="h-4 w-4 text-muted-foreground" /><strong>Actividades:</strong> {selectedCompany.activities}</div>
                              <div className="flex items-center gap-2"><Hash className="h-4 w-4 text-muted-foreground" /><strong>Versión:</strong> {selectedCompany.version}</div>
                            </div>
                          </CardContent>
                        </Card>

                        {/* Programas */}
                        <Card className="lg:col-span-2">
                          <CardHeader>
                            <CardTitle>Programas Asignados</CardTitle>
                          </CardHeader>
                          <CardContent className="space-y-4 max-h-[400px] overflow-y-auto">
                            {selectedCompany.programs.map((program) => (
                              <Card key={program.id}>
                                <CardContent className="p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                                  <div className="flex-1 w-full">
                                    <p className="font-bold">{program.code} - {program.name}</p>
                                    <div className="flex items-center gap-2 mt-2">
                                      <Progress value={program.progress} className="w-full" />
                                      <span className="text-sm font-medium">{program.progress}%</span>
                                    </div>
                                  </div>
                                  <Link href={`/programa/${program.id}?company_id=${company.id}`}>
                                    <Button variant="default" size="sm" className="mt-2 sm:mt-0">Ir al Programa</Button>
                                  </Link>
                                </CardContent>
                              </Card>
                            ))}
                          </CardContent>
                        </Card>
                      </div>
                    </TableCell>
                  </TableRow>
                )}
              </>
            ))}
          </TableBody>
        </Table>
      </div>
      </div>

    </AppLayout>
  )
}
