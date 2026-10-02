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
- Apache con `mod_rewrite` y `mod_headers` si se despliega con `.htaccess`
- XAMPP para desarrollo local

---

## Instalación

### 1. Crear la base de datos

1. Abre phpMyAdmin o tu cliente MySQL.
2. Ejecuta el contenido de `includes/database.sql`; el script crea la base `tesi_lgac`, sus tablas, categorías y el usuario administrador inicial.

### 2. Configurar la conexión

Abre `includes/config.php` y establece los valores de la base y la URL de acuerdo con el entorno:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'tesi_lgac');
define('DB_USER', 'tu_usuario_mysql');
define('DB_PASS', 'tu_contrasena_mysql');
define('BASE_URL', 'https://tu-dominio.example');
```

No publiques credenciales reales en el repositorio. Para desarrollo local con la configuración actual de XAMPP, la aplicación usa `http://localhost:8080/Proyectos/tesi_lgac` porque IIS ocupa el puerto 80; ajusta `BASE_URL` si cambias el puerto o el servidor web.

### 3. Preparar archivos subidos

- Asegúrate de que `assets/uploads/` exista y el servidor web pueda escribir en ella.
- Conserva `assets/uploads/.htaccess` para evitar la ejecución de scripts en archivos cargados.
- No incluyas fotos, currículums ni otros documentos reales de `assets/uploads/` en el repositorio. En producción, conserva allí los archivos del servidor o respáldalos por separado.
- En hosting, sube el código a `public_html/` o a la carpeta configurada para el dominio y usa HTTPS.

### 4. Ingresar al sistema

| Vista | URL | Acceso |
|-------|-----|--------|
| Portal público | `/` o `/index.php` | Cualquier persona |
| Detalle de publicación | `/publicacion.php?id=ID` | Publicación visible |
| Panel docente | `/docente/login.php` | Docentes registrados |
| Panel admin | `/admin/login.php` | Administrador |

**Credenciales iniciales del administrador:**
- Correo: `admin@tesi.edu.mx`
- Contraseña: `Tesci26!`
- La contraseña se guarda como bcrypt. Cámbiala después de la instalación y no reutilices esta clave en producción.

Si la base ya existía antes de instalar este proyecto, el correo o la contraseña pueden diferir de estos valores iniciales.

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
- ✅ Directorio `includes/` bloqueado desde el navegador
- ✅ Sesiones con cookie HttpOnly + SameSite
- ✅ Sin registro público de usuarios
