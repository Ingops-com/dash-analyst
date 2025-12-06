import { Link, Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Calendar, Plus } from 'lucide-react';

interface Reminder {
  id: number;
  title: string;
  description: string | null;
  reminder_date: string;
  status: 'pending' | 'completed' | 'cancelled';
  notified: boolean;
}

interface Props {
  reminders: {
    data: Reminder[];
  };
}

export default function RemindersIndex({ reminders }: Props) {
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
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    });
  };

  return (
    <AppLayout>
      <Head title="Recordatorios" />

      <div className="py-12">
        <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
          <div className="flex justify-between items-center mb-6">
            <div className="flex items-center gap-3">
              <Calendar className="w-8 h-8 text-blue-600" />
              <h2 className="font-semibold text-2xl text-gray-800">Mis Recordatorios</h2>
            </div>
            <Link
              href="/reminders/create"
              className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2"
            >
              <Plus className="w-4 h-4" />
              Nuevo Recordatorio
            </Link>
          </div>

          {reminders.data.length === 0 ? (
            <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-8 text-center">
              <Calendar className="w-16 h-16 text-gray-300 mx-auto mb-4" />
              <h3 className="text-xl font-semibold text-gray-700 mb-2">No hay recordatorios</h3>
              <p className="text-gray-500 mb-6">
                Crea tu primer recordatorio para organizar mejor tus tareas.
              </p>
              <Link
                href="/reminders/create"
                className="inline-block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
              >
                Crear Recordatorio
              </Link>
            </div>
          ) : (
            <div className="grid gap-4">
              {reminders.data.map((reminder) => (
                <div
                  key={reminder.id}
                  className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 hover:shadow-md transition-shadow"
                >
                  <div className="flex justify-between items-start">
                    <div className="flex-1">
                      <div className="flex items-center gap-3 mb-2">
                        <h3 className="text-lg font-semibold text-gray-900">{reminder.title}</h3>
                        <span
                          className={`inline-block px-3 py-1 rounded-full text-sm font-medium ${getStatusColor(
                            reminder.status
                          )}`}
                        >
                          {getStatusLabel(reminder.status)}
                        </span>
                      </div>
                      {reminder.description && (
                        <p className="text-gray-600 mb-3">{reminder.description}</p>
                      )}
                      <div className="text-sm text-gray-500">📅 {formatDate(reminder.reminder_date)}</div>
                      {reminder.notified && (
                        <div className="text-sm text-green-600 mt-2">✓ Notificación enviada</div>
                      )}
                    </div>
                    <div className="flex gap-2 ml-4">
                      <Link
                        href={`/reminders/${reminder.id}/edit`}
                        className="px-3 py-2 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition-colors"
                      >
                        Editar
                      </Link>
                      <Link
                        href={`/reminders/${reminder.id}`}
                        method="delete"
                        as="button"
                        className="px-3 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition-colors"
                      >
                        Eliminar
                      </Link>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      </div>
    </AppLayout>
  );
}
