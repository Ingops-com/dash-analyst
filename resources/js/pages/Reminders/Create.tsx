import React, { FormEvent, useState } from 'react';
import { Link, Head, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft } from 'lucide-react';

export default function CreateReminder() {
  const [title, setTitle] = useState('');
  const [description, setDescription] = useState('');
  const [reminderDate, setReminderDate] = useState('');
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setLoading(true);

    router.post('/reminders', {
      title,
      description,
      reminder_date: reminderDate,
    });
  };

  return (
    <AppLayout>
      <Head title="Crear Recordatorio" />

      <div className="py-12">
        <div className="max-w-2xl mx-auto sm:px-6 lg:px-8">
          <Link href="/reminders" className="flex items-center gap-2 text-blue-600 hover:text-blue-700 mb-6">
            <ArrowLeft className="w-4 h-4" />
            Volver
          </Link>

          <Card>
            <CardHeader>
              <CardTitle>Crear Nuevo Recordatorio</CardTitle>
              <CardDescription>
                Añade un nuevo recordatorio y recibirás una notificación en la fecha programada
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
                  <p className="text-sm text-gray-500 mt-1">
                    Recibirás una notificación en esta fecha y hora
                  </p>
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
                    {loading ? 'Guardando...' : 'Crear Recordatorio'}
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
