import React from 'react';
import { Link, Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft, Edit2, Trash2, CheckCircle, Clock, AlertCircle } from 'lucide-react';

interface Reminder {
  id: number;
  title: string;
  description: string | null;
  reminder_date: string;
  status: 'pending' | 'completed' | 'cancelled';
  notified: boolean;
  created_at: string;
}

interface Props {
  reminder: Reminder;
}

export default function ShowReminder({ reminder }: Props) {
  const getStatusColor = (status: string) => {
    switch (status) {
      case 'completed':
        return 'bg-green-100 text-green-800';
      case 'cancelled':
        return 'bg-red-100 text-red-800';
      case 'pending':
        return 'bg-yellow-100 text-yellow-800';
      default:
        return 'bg-gray-100 text-gray-800';
    }
  };

  const getStatusLabel = (status: string) => {
    const labels: Record<string, string> = {
      pending: 'Pendiente',
      completed: 'Completado',
      cancelled: 'Cancelado',
    };
    return labels[status] || status;
  };

  const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('es-ES', {
      day: '2-digit',
      month: 'long',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    });
  };

  return (
    <AppLayout>
      <Head title={reminder.title} />

      <div className="py-12">
        <div className="max-w-2xl mx-auto sm:px-6 lg:px-8">
          <Link href="/reminders" className="flex items-center gap-2 text-blue-600 hover:text-blue-700 mb-6">
            <ArrowLeft className="w-4 h-4" />
            Volver
          </Link>

          <Card>
            <CardHeader className="pb-3">
              <div className="flex items-start justify-between">
                <div className="flex-1">
                  <div className="flex items-center gap-3 mb-2">
                    {reminder.status === 'pending' && (
                      <Clock className="w-5 h-5 text-yellow-600" />
                    )}
                    {reminder.status === 'completed' && (
                      <CheckCircle className="w-5 h-5 text-green-600" />
                    )}
                    {reminder.status === 'cancelled' && (
                      <AlertCircle className="w-5 h-5 text-red-600" />
                    )}
                    <CardTitle className="text-2xl">{reminder.title}</CardTitle>
                  </div>
                  <span className={`inline-block px-3 py-1 rounded-full text-sm font-medium ${getStatusColor(reminder.status)}`}>
                    {getStatusLabel(reminder.status)}
                  </span>
                </div>
                <div className="flex gap-2">
                  <Link href={`/reminders/${reminder.id}/edit`}>
                    <Button variant="outline" size="sm" className="flex items-center gap-1">
                      <Edit2 className="w-4 h-4" />
                      Editar
                    </Button>
                  </Link>
                </div>
              </div>
            </CardHeader>
            <CardContent className="space-y-6">
              {reminder.description && (
                <div>
                  <h3 className="text-sm font-semibold text-gray-700 mb-2">Descripción</h3>
                  <p className="text-gray-600 whitespace-pre-wrap">{reminder.description}</p>
                </div>
              )}

              <div className="border-t pt-4">
                <div className="grid grid-cols-1 gap-4">
                  <div>
                    <h3 className="text-sm font-semibold text-gray-700 mb-1">Fecha del Recordatorio</h3>
                    <p className="text-gray-600">📅 {formatDate(reminder.reminder_date)}</p>
                  </div>

                  {reminder.notified && (
                    <div className="bg-green-50 border border-green-200 rounded-lg p-3">
                      <p className="text-sm text-green-800">
                        ✓ Se envió una notificación en la fecha programada
                      </p>
                    </div>
                  )}

                  <div>
                    <h3 className="text-sm font-semibold text-gray-700 mb-1">Creado el</h3>
                    <p className="text-sm text-gray-500">
                      {new Date(reminder.created_at).toLocaleDateString('es-ES', {
                        day: '2-digit',
                        month: '2-digit',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit',
                      })}
                    </p>
                  </div>
                </div>
              </div>

              <div className="flex gap-3 justify-end pt-4 border-t">
                <Link href="/reminders">
                  <Button variant="outline">
                    Volver
                  </Button>
                </Link>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </AppLayout>
  );
}
