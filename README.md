# 🎓 LMS IA — Cuestionarios con Google Gemini, clasificación y planificador de estudio

Sistema de Gestión del Aprendizaje (LMS) construido con **PHP 8.3, Laravel 13 + Breeze (Blade), SQLite y la API de Google Gemini**.
Sube un PDF, una imagen o una foto de tus apuntes manuscritos: la IA extrae el texto y genera automáticamente
preguntas de **opción múltiple (MCQ)** y de **selección múltiple (SATA)** con explicaciones y niveles de dificultad.
Incluye gamificación (rachas, 9 insignias y tabla de clasificación), planificador de estudio, grupos con códigos de
invitación y un panel de administración completo.

---

## ✅ Funcionalidades

| Área | Qué incluye |
| --- | --- |
| **Escaneo de documentos con IA** | PDF con texto (lectura local), PDF escaneados, imágenes JPG/PNG/WEBP/HEIC y fotos de notas manuscritas (OCR multimodal con Gemini), Word `.docx`, `.txt` y `.md`. Reintento de escaneo y descarga del original. |
| **Generación de cuestionarios** | Gemini con salida JSON estructurada (`responseSchema`): MCQ (1 correcta) y SATA (varias correctas), explicación y dificultad por pregunta, idioma configurable. Las respuestas se validan y normalizan antes de guardarse. Editor manual de preguntas. |
| **Evaluación previa y posterior** | Cada intento puede ser *práctica*, *evaluación previa* o *posterior*; confianza antes/después (1–5), reflexión final y cálculo de la **ganancia de aprendizaje**. |
| **Texto a voz (8 voces)** | Gemini TTS (Kore, Puck, Charon, Zephyr, Fenrir, Leda, Orus, Aoede) para apuntes, resúmenes y preguntas. Audio WAV en caché. Si no hay clave de API usa la voz del navegador. |
| **Planificador de estudio** | Tareas diarias automáticas según la fecha del examen: estudio → autoevaluación → repaso espaciado → simulacro final → día del examen. Reparte los minutos diarios y se puede regenerar conservando lo completado. |
| **Grupos de estudio** | Código de invitación (`ABCD-2345`) y enlace para compartir, cuestionarios compartidos, clasificación del grupo, gestión de miembros. |
| **Gamificación** | Puntos por dificultad (+25 % por puntuación perfecta), rachas diarias, **9 insignias** y tabla de clasificación **semanal, mensual e histórica**. |
| **Analítica** | Personal: evolución, actividad, aciertos por dificultad y tipo, calibración de confianza, temas a reforzar, previa vs. posterior. Global (admin): usuarios, intentos, uso y tokens de IA, distribución de notas. |
| **Notas** | Carpetas con color, etiquetas, favoritos, búsqueda y resumen con IA. |
| **Seguridad y cuentas** | Roles administrador/usuario, verificación de correo con **código OTP por SMTP**, inicio de sesión con **Google OAuth 2.0**, desactivación de cuentas, políticas de autorización y límite de peticiones a la IA. |
| **Sesiones** | El usuario ve y cierra sus sesiones; el administrador monitoriza todas y **fuerza el cierre** (por sesión o por usuario). |
| **Administración** | Usuarios, cuestionarios, sesiones, **registro de auditoría**, **anuncios** (con notificación a todos) y **copias de seguridad/restauración** de SQLite (manuales, subidas y automáticas diarias). |
| **Interfaz** | Español, tema claro/oscuro/sistema, diseño adaptable a móviles, **DataTables con exportación CSV, Excel, PDF e impresión**, gráficos con Chart.js, notificaciones en la app. |

---

## 🧱 Tecnologías

- **Backend:** PHP 8.3+, Laravel 13, Laravel Breeze (Blade), Laravel Socialite, `smalot/pdfparser`
- **IA:** API REST de Google Gemini (`generateContent`) — texto, visión/OCR, salida estructurada y TTS
- **Base de datos:** SQLite (compatible con MySQL/PostgreSQL salvo el módulo de copias integrado)
- **Frontend:** Tailwind CSS 3, Alpine.js, Vite, Chart.js, DataTables 3 + Buttons (JSZip, pdfmake), Heroicons

---

## 🚀 Instalación rápida (desarrollo)

Requisitos: PHP 8.3+ con extensiones `pdo_sqlite`, `mbstring`, `zip`, `fileinfo`, `gd`; Composer 2 y Node.js 20+.

```bash
git clone <url-del-repositorio> lms-ia && cd lms-ia
composer setup        # instala dependencias, crea .env y la BD SQLite, migra, siembra y compila assets
php artisan serve     # http://localhost:8000
```

`composer setup` equivale a:

```bash
composer install
cp .env.example .env && php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
npm install && npm run build
```

> **Subidas grandes en local:** PHP limita las subidas a 2 MB por defecto. Para escanear documentos de hasta 10 MB,
> ajusta en tu `php.ini` `upload_max_filesize = 12M` y `post_max_size = 12M` (consulta la ruta con `php --ini`).

### Cuentas de demostración

| Rol | Correo | Contraseña |
| --- | --- | --- |
| Administrador | `admin@lms.test` | `password` |
| Estudiante (con datos de ejemplo) | `demo@lms.test` | `password` |

> En producción (`APP_ENV=production`) el seeder solo crea el administrador definido en `ADMIN_EMAIL` / `ADMIN_PASSWORD` y las insignias. **Cambia esas credenciales.**

---

## ⚙️ Configuración (`.env`)

### Google Gemini

1. Crea una clave en <https://aistudio.google.com/apikey>.
2. Añádela al `.env`:

```dotenv
GEMINI_API_KEY=tu-clave
GEMINI_MODEL=gemini-flash-latest          # o un modelo concreto, p. ej. gemini-2.5-flash
GEMINI_TTS_MODEL=gemini-2.5-flash-preview-tts
GEMINI_TIMEOUT=120
```

Sin clave, la aplicación sigue funcionando: los PDF con texto, Word y `.txt` se leen localmente, el texto a voz usa
la voz del navegador y las pantallas indican que la IA no está configurada. Cada petición a la IA se registra
(tipo, modelo, tokens, duración y errores) y se muestra en la analítica global.

### Inicio de sesión con Google (OAuth 2.0)

1. En <https://console.cloud.google.com/apis/credentials> crea un *ID de cliente de OAuth* de tipo **Aplicación web**.
2. URI de redirección autorizada: `https://tu-dominio.com/auth/google/callback` (en local, `http://localhost:8000/auth/google/callback`).
3. Configura:

```dotenv
GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"
```

El botón «Continuar con Google» solo aparece cuando hay credenciales. Las cuentas de Google llegan ya verificadas;
si el correo ya existía, se vincula a esa cuenta.

### Correo SMTP (códigos OTP)

Al registrarse, el usuario recibe un **código de 6 dígitos** válido 10 minutos (máximo 5 intentos). Ejemplo con Gmail
(requiere una [contraseña de aplicación](https://myaccount.google.com/apppasswords)):

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=tu-cuenta@gmail.com
MAIL_PASSWORD=contraseña-de-aplicación
MAIL_FROM_ADDRESS=tu-cuenta@gmail.com
MAIL_FROM_NAME="${APP_NAME}"
```

Con `MAIL_MAILER=log` (valor por defecto) los correos y códigos se escriben en `storage/logs/laravel.log`.

### Otros ajustes

```dotenv
APP_TIMEZONE=America/Bogota   # afecta a rachas, planificador y clasificaciones
SESSION_DRIVER=database       # necesario para monitorizar y cerrar sesiones
LMS_MAX_UPLOAD_KB=10240       # tamaño máximo de documentos (Gemini admite 20 MB en línea)
LMS_MAX_AI_CHARS=60000        # caracteres del documento enviados a la IA
LMS_TTS_MAX_CHARS=3000        # longitud máxima por lectura en voz alta
LMS_BACKUPS_KEEP=14           # copias automáticas que se conservan
```

---

## 🌐 Despliegue en producción

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
cp .env.example .env   # APP_ENV=production, APP_DEBUG=false, APP_URL=https://tu-dominio.com
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed --force
php artisan optimize
```

- **Permisos:** el usuario del servidor web debe poder escribir en `storage/`, `bootstrap/cache/` y `database/`
  (SQLite necesita escribir también en la carpeta que contiene el archivo).
- **Tareas programadas:** añade al cron `* * * * * cd /ruta/al/proyecto && php artisan schedule:run >> /dev/null 2>&1`.
  Se ejecutan la copia de seguridad diaria (`lms:backup`, 02:00) y la limpieza de registros antiguos (`model:prune`).
- **PHP:** la generación de cuestionarios puede tardar hasta un minuto; usa `max_execution_time ≥ 180` y en Nginx
  `fastcgi_read_timeout 180;`. Ajusta `upload_max_filesize`/`post_max_size` (≥ 12M) y `client_max_body_size 12M;`.

Ejemplo de bloque Nginx:

```nginx
server {
    listen 80;
    server_name tu-dominio.com;
    root /var/www/lms-ia/public;
    index index.php;
    client_max_body_size 12M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_read_timeout 180;
    }

    location ~ /\.(?!well-known) {
        deny all;
    }
}
```

### Copias de seguridad

Desde **Administración → Copias de seguridad** puedes crear, descargar, subir y restaurar copias de la base de datos
SQLite (se generan con `VACUUM INTO`, sin bloquear la aplicación). Antes de restaurar se guarda automáticamente una
copia `pre-restore`. Desde la consola:

```bash
php artisan lms:backup            # crea una copia y conserva las LMS_BACKUPS_KEEP más recientes
php artisan lms:backup --keep=30
```

Las copias se guardan en `storage/app/backups`. Con MySQL o PostgreSQL usa `mysqldump`/`pg_dump`.

---

## 🧪 Tests

```bash
php artisan test     # o: composer test
./vendor/bin/pint    # estilo de código
```

La suite cubre autenticación (OTP, Google OAuth, cuentas desactivadas), escaneo de documentos y generación de
cuestionarios (con `Http::fake()` para Gemini), corrección MCQ/SATA, puntos, rachas e insignias, planificador,
grupos, clasificaciones, texto a voz, panel de administración, sesiones, copias de seguridad y una prueba de humo
que renderiza todas las pantallas.

---

## 🗂️ Estructura principal

```
app/
├── Enums/                    Role, QuestionType (MCQ/SATA), Difficulty, AssessmentType, TaskType
├── Http/Controllers/         Notas, cuestionarios, intentos, TTS, planificador, grupos, clasificación…
│   └── Admin/                Analítica, usuarios, sesiones, auditoría, anuncios, copias de seguridad
├── Http/Middleware/          EnsureUserHasRole (role:admin), EnsureUserIsActive
├── Models/                   User, Note, Folder, Tag, Quiz, Question, QuizAttempt, Badge, StudyPlan…
├── Policies/                 Autorización por recurso
├── Services/
│   ├── Ai/                   GeminiClient, DocumentScanner, QuizGenerator, TextToSpeech
│   ├── Auth/OtpService.php   Verificación de correo con código de un solo uso
│   ├── GamificationService   Puntos, rachas e insignias
│   ├── StudyPlanGenerator    Tareas diarias automáticas
│   ├── LeaderboardService    Clasificación semanal/mensual/histórica
│   ├── AnalyticsService      Métricas personales y globales
│   ├── BackupService         Copias y restauración SQLite
│   └── ActivityLogger        Registro de auditoría
config/lms.php                Ajustes del LMS (subidas, voces, OTP, copias…)
resources/js/                 Alpine (quiz, TTS, tema), DataTables y Chart.js con carga diferida
```

## 🏅 Insignias

| | Insignia | Cómo se consigue |
| --- | --- | --- |
| 🚀 | Primer paso | Completa tu primer cuestionario |
| 💯 | Perfección | Obtén un 100 % |
| 🔥 | En racha | 3 días seguidos |
| ⚡ | Imparable | 7 días seguidos |
| 👑 | Leyenda | 30 días seguidos |
| 🧠 | Maestro de cuestionarios | 10 cuestionarios completados |
| 📚 | Bibliotecario | 5 documentos |
| 🤝 | Aprendiz social | Crea o únete a un grupo |
| 🗓️ | Planificador experto | 10 tareas del planificador completadas |

## 📄 Licencia

MIT.
