import { useRef, useState, useMemo } from 'react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { Transition } from '@headlessui/react';

import { type BreadcrumbItem, type SharedData } from '@/types';
import { send } from '@/routes/verification';
import { edit, update } from '@/routes/profile';

import DeleteUser from '@/components/delete-user';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { useInitials } from '@/hooks/use-initials';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Configuración de perfil',
    href: edit().url,
  },
];

type ProfileProps = {
  mustVerifyEmail: boolean;
  status?: string;
};

export default function Profile({ mustVerifyEmail, status }: ProfileProps) {
  const { auth } = usePage<SharedData>().props;

  // Hooks / estado local
  const fileInputRef = useRef<HTMLInputElement>(null);
  const [photoPreview, setPhotoPreview] = useState<string | null>(null);
  const initials = useInitials(auth.user?.name ?? '');

  // useForm de Inertia (no existe <Form /> en @inertiajs/react)
  const { data, setData, post, processing, errors, recentlySuccessful, clearErrors } = useForm<{
    name: string;
    username: string;
    email: string;
    photo: File | null;
    _method?: 'PUT' | 'PATCH';
    remove_photo?: boolean;
  }>({
    name: auth.user?.name ?? '',
    username: auth.user?.username ?? '',
    email: auth.user?.email ?? '',
    photo: null,
    _method: 'PUT', // típico para rutas tipo resource update
    remove_photo: false,
  });

  // Limpieza de URL.createObjectURL cuando cambie la imagen
  useMemo(() => {
    return () => {
      if (photoPreview) URL.revokeObjectURL(photoPreview);
    };
  }, [photoPreview]);

  // Manejadores
  const handlePhotoChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (file) {
      setData('photo', file);
      setData('remove_photo', false);
      setPhotoPreview(URL.createObjectURL(file));
      if (errors.photo) clearErrors('photo');
    }
  };

  const handleRemovePhoto = () => {
    // Marcamos para que el backend elimine la foto
    setData('photo', null);
    setData('remove_photo', true);
    setPhotoPreview(null);
    if (fileInputRef.current) {
      fileInputRef.current.value = '';
    }
  };

  const onSubmit = (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    post(update().url, {
      forceFormData: true, // necesario para subir archivos
      preserveScroll: true,
      onSuccess: () => {
        // Si se guardó, limpiar solo el input de archivo (no los demás cambios)
        setData('photo', null);
        if (fileInputRef.current) {
          fileInputRef.current.value = '';
        }
      },
    });
  };

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Configuración de perfil" />

      <SettingsLayout>
        <div className="space-y-6">
          {/* --- FORMULARIO --- */}
          <form onSubmit={onSubmit} className="space-y-6" encType="multipart/form-data">
            {/* Tarjeta: Foto de Perfil */}
            <Card>
              <CardHeader>
                <CardTitle>Foto de Perfil</CardTitle>
                <CardDescription>Sube o actualiza tu foto de perfil.</CardDescription>
              </CardHeader>
              <CardContent className="flex items-center gap-6">
                <Avatar className="h-20 w-20">
                  <AvatarImage src={photoPreview ?? auth.user?.photo_url ?? undefined} />
                  <AvatarFallback>{initials}</AvatarFallback>
                </Avatar>

                <div className="flex flex-col gap-2">
                  <div className="flex gap-2">
                    <input
                      type="file"
                      ref={fileInputRef}
                      onChange={handlePhotoChange}
                      className="hidden"
                      accept="image/png, image/jpeg"
                    />
                    <Button
                      type="button"
                      variant="outline"
                      onClick={() => fileInputRef.current?.click()}
                    >
                      Subir foto
                    </Button>
                    <Button type="button" variant="ghost" onClick={handleRemovePhoto}>
                      Eliminar
                    </Button>
                  </div>
                  <InputError className="mt-1" message={errors.photo} />
                </div>
              </CardContent>
            </Card>

            {/* Tarjeta: Datos Generales */}
            <Card>
              <CardHeader>
                <CardTitle>Datos Generales</CardTitle>
                <CardDescription>Actualiza tu nombre, correo electrónico y nombre de usuario.</CardDescription>
              </CardHeader>

              <CardContent className="space-y-4">
                <div className="space-y-2">
                  <Label htmlFor="name">Nombre</Label>
                  <Input
                    id="name"
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    required
                  />
                  <InputError className="mt-1" message={errors.name} />
                </div>

                <div className="space-y-2">
                  <Label htmlFor="username">Nombre de usuario</Label>
                  <Input
                    id="username"
                    value={data.username}
                    onChange={(e) => setData('username', e.target.value)}
                    required
                  />
                  <InputError className="mt-1" message={errors.username} />
                </div>

                <div className="space-y-2">
                  <Label htmlFor="email">Correo Electrónico</Label>
                  <Input
                    id="email"
                    type="email"
                    value={data.email}
                    onChange={(e) => setData('email', e.target.value)}
                    required
                  />
                  <InputError className="mt-1" message={errors.email} />
                </div>

                {mustVerifyEmail && auth.user?.email_verified_at === null && (
                  <div>
                    <p className="text-sm text-muted-foreground">
                      Tu dirección de correo electrónico no está verificada.{' '}
                      <Link
                        href={send()}
                        method="post"
                        as="button"
                        className="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                      >
                        Haz clic aquí para reenviar el correo de verificación.
                      </Link>
                    </p>

                    {status === 'verification-link-sent' && (
                      <div className="mt-2 font-medium text-sm text-green-600">
                        Se ha enviado un nuevo enlace de verificación a tu dirección de correo electrónico.
                      </div>
                    )}
                  </div>
                )}
              </CardContent>

              <CardFooter className="flex items-center justify-end gap-4">
                <Transition
                  show={recentlySuccessful}
                  enter="transition ease-in-out"
                  enterFrom="opacity-0"
                  leave="transition ease-in-out"
                  leaveTo="opacity-0"
                >
                  <p className="text-sm text-muted-foreground">Guardado.</p>
                </Transition>

                <Button type="submit" disabled={processing}>
                  {processing ? 'Guardando…' : 'Guardar Cambios'}
                </Button>
              </CardFooter>
            </Card>
          </form>
        </div>

        <DeleteUser />
      </SettingsLayout>
    </AppLayout>
  );
}
