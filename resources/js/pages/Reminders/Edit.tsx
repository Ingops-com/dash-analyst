import React, { FormEvent, useState } from 'react';
import { Link, Head, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';

interface Reminder {
  id: number;
  title: string;
  description: string | null;
  reminder_date: string;
  status: 'pending' | 'completed' | 'cancelled';
}

interface Props {
  reminder: Reminder;
}

export default function EditReminder({ reminder }: Props) {
  const [title, setTitle] = useState(reminder.title);
  const [description, setDescription] = useState(reminder.description || '');
  
  // Convertir la fecha al formato datetime-local (YYYY-MM-DDTHH:mm)
  const formatDateForInput = (dateString: string) => {
    const date = new Date(dateString);
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    return `${year}-${month}-${day}T${hours}:${minutes}`;
  };
  
  const [reminderDate, setReminderDate] = useState(formatDateForInput(reminder.reminder_date));
  const [status, setStatus] = useState<'pending' | 'completed' | 'cancelled'>(reminder.status);
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setLoading(true);

    router.put(`/reminders/${reminder.id}`, {
      title,
      description,
      reminder_date: reminderDate,
      status,
    });
  };

  return (
    <AppLayout>
      <Head title={`Editar Recordatorio: ${reminder.title}`} />

      <div className="py-12">
        <div className="max-w-2xl mx-auto sm:px-6 lg:px-8">
          <Link href="/reminders" className="flex items-center gap-2 text-blue-600 hover:text-blue-700 mb-6">
            <ArrowLeft className="w-4 h-4" />
            Volver
          </Link>

          <Card>
            <CardHeader>
              <CardTitle>Editar Recordatorio</CardTitle>
              <CardDescription>
                Modifica los detalles de tu recordatorio
              </CardDescription>
            </CardHeader>
            <CardContent>
              <form onSubmit={handleSubmit} className="space-y-6">
                <div>
                  <label htmlFor="title" className="block text-sm font-medium text-gray-700 mb-2">
                    Título del Recordatorio *
                  </label>
                  <Input
                    id="title"
                    type="text"
                    value={title}
                    onChange={(e) => setTitle(e.target.value)}
                    placeholder="Ej: Reunión importante"
                    required
                    maxLength={255}
                  />
                </div>

                <div>
                  <label htmlFor="description" className="block text-sm font-medium text-gray-700 mb-2">
                    Descripción
                  </label>
                  <Textarea
                    id="description"
                    value={description}
                    onChange={(e) => setDescription(e.target.value)}
                    placeholder="Añade más detalles sobre el recordatorio..."
                    rows={4}
                  />
                </div>

                <div>
                  <label htmlFor="reminderDate" className="block text-sm font-medium text-gray-700 mb-2">
                    Fecha y Hora del Recordatorio *
                  </label>
                  <Input
                    id="reminderDate"
                    type="datetime-local"
                    value={reminderDate}
                    onChange={(e) => setReminderDate(e.target.value)}
                    required
                  />
                </div>

                <div>
                  <label htmlFor="status" className="block text-sm font-medium text-gray-700 mb-2">
                    Estado *
                  </label>
                  <Select value={status} onValueChange={(value: any) => setStatus(value)}>
                    <SelectTrigger>
                      <SelectValue placeholder="Selecciona un estado" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="pending">Pendiente</SelectItem>
                      <SelectItem value="completed">Completado</SelectItem>
                      <SelectItem value="cancelled">Cancelado</SelectItem>
                    </SelectContent>
                  </Select>
                </div>

                <div className="flex gap-3 justify-end pt-4">
                  <Link href="/reminders">
                    <Button variant="outline">
                      Cancelar
                    </Button>
                  </Link>
                  <Button
                    type="submit"
                    disabled={loading}
                  >
                    {loading ? 'Guardando...' : 'Actualizar Recordatorio'}
                  </Button>
                </div>
              </form>
            </CardContent>
          </Card>
        </div>
      </div>
    </AppLayout>
  );
}
