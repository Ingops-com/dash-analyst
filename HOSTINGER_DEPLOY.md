# 🚀 Guía de Despliegue en Hostinger

## Información del Servidor
- **OS**: CloudLinux 8 (basado en RHEL 8)
- **Panel**: hPanel (Hostinger)
- **Directorio Web**: `/home/usuario/public_html`
- **PHP**: Versión gestionada por hPanel

---

## ⚠️ IMPORTANTE: Estructura de Hostinger

En Hostinger, la estructura es:
```
/home/tu_usuario/
├── public_html/          # AQUÍ van tus archivos Laravel
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── public/           # Contenido público (index.php, CSS, JS)
│   ├── resources/
│   ├── routes/
│   ├── storage/
│   ├── vendor/
│   └── artisan
├── domains/              # Si tienes múltiples dominios
└── tmp/                  # Archivos temporales
```

**CRÍTICO**: En Hostinger, el `DocumentRoot` debe apuntar a `/public_html/public` o mover el contenido de `public/` a la raíz.

---

## 📋 PASO 1: Verificar Ubicación y Permisos

```bash
# Conectar por SSH (desde hPanel > Avanzado > SSH)
ssh u123456789@tu-dominio.com -p 65002

# Verificar dónde estás
pwd
# Deberías estar en: /home/u123456789

# Ir a public_html
cd public_html

# Listar archivos descomprimidos
ls -la

# Deberías ver: app, bootstrap, config, database, public, etc.
```

---

## 📋 PASO 2: Configurar Permisos (NO usar sudo en Hostinger)

```bash
# Hostinger NO permite sudo, pero eres dueño de tus archivos
# Configurar permisos para storage y cache

cd ~/public_html

# Storage
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# Verificar permisos
ls -la storage/
ls -la bootstrap/cache/
```

---

## 📋 PASO 3: Configurar .env

```bash
# Copiar ejemplo
cd ~/public_html
cp .env.example .env

# Editar con nano o vim
nano .env
```

### Configuración .env para Hostinger:

```env
APP_NAME="Dash Analyst"
APP_ENV=production
APP_KEY=                    # Se genera después
APP_DEBUG=false             # ¡IMPORTANTE!
APP_URL=https://tu-dominio.com

# Base de datos (desde hPanel > Base de datos)
DB_CONNECTION=mysql
DB_HOST=localhost           # Generalmente localhost
DB_PORT=3306
DB_DATABASE=u123456789_nombre_bd
DB_USERNAME=u123456789_nombre_bd
DB_PASSWORD=tu_contraseña_bd

# Logs
LOG_CHANNEL=daily
LOG_LEVEL=error

# Session y Cache
SESSION_DRIVER=database
SESSION_LIFETIME=120
CACHE_STORE=database
FILESYSTEM_DISK=public
QUEUE_CONNECTION=database
```

**Guardar**: `CTRL+O`, `Enter`, `CTRL+X`

---

## 📋 PASO 4: Configurar PHP (desde hPanel)

### En el Panel de Hostinger:

1. Ve a **hPanel** > **Avanzado** > **Configuración PHP**
2. Selecciona **PHP 8.2** (o la versión que tengas)
3. Verifica que estas extensiones estén habilitadas:
   - ✅ mbstring
   - ✅ openssl
   - ✅ pdo
   - ✅ pdo_mysql
   - ✅ tokenizer
   - ✅ xml
   - ✅ ctype
   - ✅ json
   - ✅ bcmath
   - ✅ fileinfo
   - ✅ zip

4. Aumenta límites (si puedes):
   - `memory_limit`: 256M
   - `max_execution_time`: 300
   - `upload_max_filesize`: 20M
   - `post_max_size`: 20M

---

## 📋 PASO 5: Inicializar Laravel (SSH)

```bash
cd ~/public_html

# Generar APP_KEY
php artisan key:generate

# Verificar que se generó
grep APP_KEY .env

# Crear symlink para storage
php artisan storage:link

# Verificar symlink
ls -la public/storage
```

---

## 📋 PASO 6: Configurar Base de Datos

### Opción A: Importar desde hPanel (RECOMENDADO)

1. Ve a **hPanel** > **Base de datos** > **phpMyAdmin**
2. Selecciona tu base de datos
3. Clic en **Importar**
4. Sube tu archivo `.sql`
5. Clic en **Continuar**

### Opción B: Importar desde SSH

```bash
# Subir tu archivo SQL primero (con FTP/SFTP)
# Luego desde SSH:

cd ~/public_html

# Importar
mysql -u u123456789_nombre_bd -p u123456789_nombre_bd < /home/u123456789/backup.sql

# Introducir contraseña cuando se solicite
```

---

## 📋 PASO 7: Configurar DocumentRoot (CRÍTICO)

En Hostinger, tienes 2 opciones:

### Opción A: Cambiar DocumentRoot (Recomendado)

1. Ve a **hPanel** > **Dominios**
2. Busca tu dominio y haz clic en "⚙️"
3. En **Raíz del Documento**, cambia:
   - De: `/public_html`
   - A: `/public_html/public`
4. Guardar

### Opción B: Mover archivos de public/ a raíz (Alternativa)

```bash
cd ~/public_html

# Mover contenido de public/ a la raíz
mv public/* ./
mv public/.htaccess ./

# Eliminar carpeta public vacía
rmdir public

# Editar index.php para ajustar rutas
nano index.php
```

En `index.php`, cambiar:
```php
// De:
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

// A:
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
```

**Guardar**: `CTRL+O`, `Enter`, `CTRL+X`

---

## 📋 PASO 8: Cachear Configuración (Producción)

```bash
cd ~/public_html

# Limpiar cachés anteriores
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Cachear para producción
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Verificar
php artisan route:list
```

---

## 📋 PASO 9: Verificar Configuración de Apache (.htaccess)

```bash
cd ~/public_html/public
# O si moviste los archivos: cd ~/public_html

# Verificar .htaccess existe
cat .htaccess
```

Debería contener:
```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

---

## 📋 PASO 10: SSL/HTTPS (desde hPanel)

1. Ve a **hPanel** > **Seguridad** > **SSL**
2. Activa **SSL Gratis (Let's Encrypt)**
3. Espera 5-10 minutos para que se active
4. Forzar HTTPS:

```bash
nano ~/public_html/public/.htaccess
# O: nano ~/public_html/.htaccess (si moviste los archivos)
```

Agregar al inicio (después de `RewriteEngine On`):
```apache
RewriteEngine On

# Forzar HTTPS
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# ... resto del archivo
```

---

## 📋 PASO 11: Verificaciones Finales

```bash
cd ~/public_html

# 1. Verificar .env
grep APP_KEY .env
grep APP_DEBUG .env
grep DB_DATABASE .env

# 2. Verificar permisos
ls -la storage/
ls -la bootstrap/cache/

# 3. Verificar symlink
ls -la public/storage

# 4. Probar artisan
php artisan --version
php artisan route:list

# 5. Ver logs en tiempo real (si hay errores)
tail -f storage/logs/laravel.log
```

### Probar en el Navegador:
- Visita: `https://tu-dominio.com`
- Deberías ver la página de login
- Si ves error 500, revisa logs

---

## 🔧 COMANDOS ÚTILES EN HOSTINGER

```bash
# Ver versión de PHP
php -v

# Ver extensiones instaladas
php -m

# Ver logs de Laravel
tail -50 ~/public_html/storage/logs/laravel.log

# Ver logs de Apache
tail -50 ~/logs/error_log

# Limpiar todo caché
cd ~/public_html
php artisan optimize:clear

# Regenerar caché
php artisan optimize

# Verificar conexión a BD
php artisan tinker
# Luego: DB::connection()->getPdo();
```

---

## 🆘 Troubleshooting Común en Hostinger

### Error 500 - Internal Server Error

```bash
# Ver el error exacto
tail -50 ~/public_html/storage/logs/laravel.log

# Verificar permisos
chmod -R 775 ~/public_html/storage
chmod -R 775 ~/public_html/bootstrap/cache

# Limpiar y regenerar caché
php artisan config:clear
php artisan config:cache
```

### Error "No application encryption key"

```bash
cd ~/public_html
php artisan key:generate
cat .env | grep APP_KEY
```

### Error de Base de Datos

```bash
# Verificar credenciales en .env
cat ~/public_html/.env | grep DB_

# Probar conexión manual
mysql -u tu_usuario -p -h localhost tu_base_datos
```

### CSS/JS no se cargan

```bash
# Verificar APP_URL en .env
grep APP_URL ~/public_html/.env

# Debe ser: APP_URL=https://tu-dominio.com (sin / al final)

# Limpiar caché
php artisan config:clear
php artisan config:cache
```

### Archivos no se suben

```bash
# Verificar permisos de storage
ls -la ~/public_html/storage/app/public/

# Verificar symlink
ls -la ~/public_html/public/storage

# Recrear si es necesario
php artisan storage:link
```

---

## 📊 Estructura Final en Hostinger

```
/home/u123456789/
├── public_html/
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── public/              ← DocumentRoot apunta aquí
│   │   ├── build/           (CSS/JS compilados)
│   │   ├── storage/         (symlink)
│   │   ├── index.php
│   │   └── .htaccess
│   ├── resources/
│   ├── routes/
│   ├── storage/
│   │   ├── app/public/      (archivos subidos)
│   │   └── logs/
│   ├── vendor/
│   ├── .env                 (APP_DEBUG=false)
│   └── artisan
└── logs/
    └── error_log            (logs de Apache)
```

---

## ✅ Checklist Final

- [ ] Archivos descomprimidos en `/home/usuario/public_html`
- [ ] `.env` creado y configurado (APP_DEBUG=false)
- [ ] APP_KEY generado
- [ ] Base de datos importada
- [ ] Permisos configurados (775 storage y bootstrap/cache)
- [ ] Storage link creado
- [ ] DocumentRoot apunta a `/public_html/public`
- [ ] Configuración cacheada
- [ ] SSL activado
- [ ] HTTPS forzado en .htaccess
- [ ] Aplicación accesible desde el navegador
- [ ] Login funciona
- [ ] Subida de archivos funciona
- [ ] Generación de documentos funciona

---

## 📞 Siguiente Paso

Una vez completados todos los pasos, prueba:

1. Acceder a: `https://tu-dominio.com`
2. Iniciar sesión
3. Crear/editar una empresa
4. Subir un archivo de anexo
5. Generar un documento

Si todo funciona: **¡Despliegue exitoso!** 🎉

---

**Nota**: Si encuentras errores, siempre revisa primero:
```bash
tail -50 ~/public_html/storage/logs/laravel.log
```
