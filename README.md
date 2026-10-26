# Portal del Cuerpo Académico LGAC · TESCI

Sistema web para difundir publicaciones, proyectos y actividades del Cuerpo Académico LGAC del Tecnológico de Estudios Superiores de Cuautitlán Izcalli (TESCI). Incluye un portal público, un panel de administración para gestionar integrantes y un panel privado para docentes.

---

## Estructura de carpetas

tesci_lgac/
├── index.php              ← Vista pública (sin login)
├── publicacion.php        ← Detalle de publicación pública
├── .htaccess              ← Seguridad general
├── README.md              ← Este archivo
│
├── includes/
│   ├── config.php         ← ⚠️ EDITA AQUÍ tus datos de BD
│   ├── auth.php           ← Funciones de sesión y seguridad
│   └── database.sql       ← Script SQL para crear la BD
│
├── docente/
│   ├── login.php          ← Acceso de docentes
│   ├── panel.php          ← Ver y gestionar mis publicaciones
│   ├── nueva_pub.php      ← Crear publicación
│   ├── editar_pub.php     ← Editar publicación
│   ├── eliminar_pub.php   ← Eliminar publicación
│   ├── perfil.php         ← Editar perfil, foto, contraseña
│   └── logout.php
│
├── admin/
│   ├── login.php          ← Acceso de administrador
│   ├── panel.php          ← Gestionar docentes
│   ├── nuevo_docente.php  ← Registrar docente
│   ├── toggle_docente.php ← Activar/desactivar docente
│   ├── eliminar_docente.php ← Eliminar docente y sus publicaciones
│   └── logout.php
│
└── assets/
  ├── logo.png           ← Logotipo institucional
  ├── css/main.css       ← Estilos de paneles
  └── uploads/           ← Fotos y adjuntos cargados por usuarios
```

---

## Requisitos

- PHP 8.0 o posterior con PDO y PDO MySQL habilitados
- MySQL o MariaDB
- Apache con `mod_rewrite`, `mod_headers` y autorización `.htaccess` habilitada (`AllowOverride`) si se usa Apache
- XAMPP para desarrollo local

---

## Instalación

### 1. Crear la base de datos

1. Abre phpMyAdmin o tu cliente MySQL.
2. Ejecuta el contenido de `includes/database.sql`; el script crea la base `tesi_lgac`, sus tablas, categorías y el usuario administrador inicial.

### 2. Configurar la conexión

Configura estas variables de entorno en el servidor (por ejemplo, desde el panel del hosting):

```text
DB_HOST=localhost
DB_NAME=nombre_de_la_base
DB_USER=usuario_mysql
DB_PASS=contrasena_mysql
BASE_URL=https://tu-dominio.example
SESSION_COOKIE_SECURE=true
```

No publiques credenciales reales en el repositorio ni reutilices la cuenta `root` de MySQL en producción. `BASE_URL` debe coincidir con la URL pública y usar HTTPS. `SESSION_COOKIE_SECURE=true` hace que el navegador solo envíe la cookie de sesión por HTTPS; configura también el certificado y la redirección HTTPS en el panel del hosting antes de habilitarlo. Si el hosting termina HTTPS en un proxy, establece la variable explícitamente en `true`.

En XAMPP local, si no defines esas variables, se usan los valores locales de `includes/config.php` y la cookie segura se activa automáticamente cuando PHP detecta HTTPS.

### 3. Preparar archivos subidos

- Asegúrate de que `assets/uploads/` exista y el servidor web pueda escribir en ella.
- Conserva `assets/uploads/.htaccess` para evitar la ejecución de scripts en archivos cargados.
- No incluyas fotos, currículums ni otros documentos reales de `assets/uploads/` en el repositorio. En producción, conserva allí los archivos del servidor o respáldalos por separado.
- En hosting, sube el código a `public_html/` o a la carpeta configurada para el dominio y usa HTTPS. Conserva los `.htaccess`; comprueba con el proveedor que Apache permite sus reglas de acceso.

### 4. Ingresar al sistema

| Vista | URL | Acceso |
|-------|-----|--------|
| Portal público | `/` o `/index.php` | Cualquier persona |
| Detalle de publicación | `/publicacion.php?id=ID` | Publicación visible |
| Panel docente | `/docente/login.php` | Docentes registrados |
| Panel admin | `/admin/login.php` | Administrador |

La base nueva crea la cuenta administradora `223107219@cuautitlan.tecnm.mx` con una contraseña aleatoria que no se distribuye. El enlace de recuperación del administrador envía el código al `admin_recovery_email` configurado; no requiere escribir el correo usado para iniciar sesión. Antes de habilitar el acceso público, configura el correo y solicita un código para definir una contraseña nueva. El script no cambia las cuentas de una base de datos que ya existía.

### Recuperar contraseña del administrador

Solo la página de inicio de sesión del administrador ofrece **¿Olvidaste tu contraseña?**. El código del administrador llega al buzón alternativo `admin_recovery_email`; los docentes no recuperan su contraseña por correo. Si un docente la olvida, el administrador la restablece desde su panel de gestión. El código de administrador vence a los 10 minutos, solo se puede usar una vez y su hash se guarda en la base de datos.

Las tablas `recuperacion_solicitudes` y `recuperacion_tokens` tienen propósitos distintos:

- `recuperacion_solicitudes` guarda hashes del correo y la IP, no contraseñas ni códigos. Se usa para limitar solicitudes y validaciones; los registros antiguos se limpian.
- `recuperacion_tokens` guarda el hash del código, el usuario, su expiración y cuándo se usó. El código original solo se envía por correo; no queda guardado en la base.

#### Configurar Gmail en XAMPP

1. Instala las dependencias desde la carpeta del proyecto con `composer install` (PHPMailer se instala en `vendor/`, que no se sube al repositorio).
2. Copia `includes/mail.local.example.php` como `includes/mail.local.php`.
3. `includes/mail.local.php` ya está preparado localmente con `ado399512@gmail.com` como cuenta emisora y buzón de recuperación del administrador. En el archivo agrega la contraseña de aplicación de Gmail en `password`.
4. Activa la verificación en dos pasos de Google y genera una **contraseña de aplicación** para este sistema. Escríbela en `password`; no uses ni compartas la contraseña normal de Google. Consulta la [ayuda oficial de Google para contraseñas de aplicación](https://support.google.com/accounts/answer/185833).
5. `mail.local.php` está excluido de Git y protegido del acceso web por `includes/.htaccess`. Nunca envíes la contraseña de aplicación por chat ni la guardes en Git.
6. Solicita una recuperación de prueba y revisa la bandeja de entrada y spam del destinatario.

La recuperación por SMS no está habilitada: necesitaría una cuenta y credenciales de un proveedor de mensajería. En desarrollo local, `BASE_URL` usa `localhost`; solicita y valida los códigos desde la computadora donde corre XAMPP.

Para una base existente, aplica `includes/password_recovery_migration.sql` solo si aún no existen las tablas de recuperación. El administrador puede restablecer la contraseña de un docente desde su panel; la recuperación por correo está reservada exclusivamente a la cuenta administradora.

---

## Flujo de uso

```
Administrador
  └── Registra docentes (nombre, correo, contraseña temporal)
  └── Puede activar o desactivar acceso
  └── Puede eliminar docentes; esto elimina también sus publicaciones y adjuntos

Docente
  └── Inicia sesión en /docente/login.php
  └── Edita su perfil (foto, grado, especialidad)
  └── Crea publicaciones (título, categoría, descripción, archivos)
  └── Decide si cada publicación es visible en el portal público
  └── Puede marcar publicaciones como "destacadas"

Público
  └── Consulta publicaciones públicas del último mes en un carrusel responsivo
  └── Abre cada publicación en una página de detalle con descripción y adjuntos
  └── Consulta integrantes y perfiles públicos sin necesidad de registro
```

---

## Categorías de publicaciones disponibles

- 🔬 Proyecto de investigación
- 🎓 Tesis dirigida
- 📖 Libro / Capítulo
- 📄 Artículo / Ponencia
- 📅 Evento académico
- 🏆 Reconocimiento / Premio
- 🖼️ Fotografías
- 📎 Otro

---

## Archivos permitidos

| Tipo | Extensiones |
|------|-------------|
| Imágenes | .jpg, .jpeg, .png, .webp, .gif |
| Documentos | .pdf, .doc, .docx, .ppt, .pptx |
| Tamaño máximo | 10 MB por archivo |

---

## Seguridad incluida

- ✅ Contraseñas hasheadas con bcrypt (costo 12)
- ✅ Protección CSRF en todos los formularios
- ✅ Consultas con PDO y parámetros preparados (anti SQL injection)
- ✅ Validación de tipo MIME en subida de archivos
- ✅ PHP no ejecutable en carpeta uploads
- ✅ Archivos sensibles y directorio `includes/` bloqueados desde el navegador
- ✅ Sesiones con cookie HttpOnly + SameSite y Secure cuando se usa HTTPS
- ✅ Rutas administrativas verifican el rol vigente y desactivan sesiones de cuentas inactivas
- ✅ Cambios de estado, eliminaciones y cierre de sesión usan POST con token CSRF
- ✅ El inicio de sesión solo acepta contraseñas hasheadas con `password_verify()`
- ✅ Sin registro público de usuarios

Las cuentas que aún tengan contraseñas guardadas en texto plano ya no podrán iniciar sesión: restablécelas mediante el flujo **¿Olvidaste tu contraseña?** después de configurar el correo, o restablécelas desde una cuenta administradora antes de publicar el sitio. El script de instalación también trae una contraseña inicial de administrador conocida; cámbiala y verifica que no siga activa antes de exponer el hosting públicamente.
