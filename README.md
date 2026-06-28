# 💰 Gastos Bot

Bot de Telegram para llevar el control de gastos compartidos (pensado para una
pareja o un grupo reducido). Permite registrar gastos categorizados, fijar
límites mensuales por categoría con histórico, recibir alertas al acercarse al
límite y consultar resúmenes del mes actual o de meses pasados.

Se maneja desde un **menú con botones** (sin recordar comandos) y permite
registrar un gasto enviando simplemente la **foto de un ticket**, que el bot lee
automáticamente.

Construido con **Symfony 8.1** y **PHP 8.4+**, almacena los datos en
**MariaDB/MySQL** y se comunica con Telegram mediante **webhook**.

---

## ✨ Funcionalidades

- **Menú guiado con botones**: `/start` abre un menú navegable con las opciones
  agrupadas (registrar, resumen, gráficos, categorías, límites, fijos…), sin
  necesidad de recordar comandos.
- **Lectura de tickets por foto** 🧾: envías la foto de un ticket y el bot extrae
  el importe, la fecha y el comercio, y propone una categoría (visión con Google
  Gemini). Lo confirmas o lo ajustas con botones antes de guardarlo.
- **Registro de gastos** categorizados, con descripción opcional y atribución al
  usuario que lo registra.
- **Categorías** personalizables (admiten varias palabras, p. ej. `Comida fuera`).
- **Límites mensuales por categoría con histórico**: cada cambio de máximo crea
  una nueva entrada con su fecha de vigencia, de modo que los meses anteriores
  conservan el límite que tenían entonces.
- **Alertas de presupuesto automáticas**: al registrar un gasto que cruza el
  **80 %** (🟡) o el **100 %** (🔴) de una categoría, el bot avisa al resto de
  usuarios.
- **Resúmenes mensuales** con semáforos por categoría, porcentaje consumido,
  saldo restante y total gastado. Consultables para el mes actual, el anterior o
  cualquier `MM/AAAA`.
- **Gráficos** enviados como imagen: reparto por categoría (donut), gastado vs.
  límite (barras) y evolución de **gastos vs. ingresos** de los últimos meses.
- **Gastos recurrentes** (alquiler, suscripciones, nómina…) que se generan solos
  el día indicado de cada mes. El día admite **valores negativos contando desde
  el final** (`-1` = último día, `-2` = penúltimo…).
- **Edición y borrado** de gastos ya registrados.
- **Resumen automático programado** (semanal y/o cierre de mes) con sus gráficos,
  enviado a todos los usuarios mediante tareas cron.
- **Lista blanca de usuarios**: solo los IDs de Telegram autorizados pueden usar
  el bot.

---

## 🤖 Comandos del bot

| Comando | Alias | Descripción |
|---|---|---|
| `/gasto <importe> <categoría> [descripción]` | `/g` | Registra un gasto |
| `/ultimos [n]` | `/lista` | Últimos gastos con su ID |
| `/editar <id> <importe\|categoria\|descripcion> <valor>` | `/edit` | Corrige un gasto |
| `/borrar <id>` | `/eliminar`, `/del` | Elimina un gasto |
| `/nuevacategoria <nombre>` | `/nuevacat` | Crea una categoría |
| `/categorias` | `/cats` | Lista las categorías |
| `/limite <categoría> <importe>` | `/presupuesto` | Fija el máximo mensual de una categoría |
| `/limites` | `/presupuestos` | Muestra los máximos vigentes |
| `/resumen [pasado \| MM/AAAA]` | `/informe`, `/r` | Situación del mes (texto + gráficos) |
| `/grafico [categorias\|limites\|evolucion] [MM/AAAA]` | `/grafica` | Envía un gráfico como imagen |
| `/recurrente <día> <importe> <categoría> [desc]` | `/fijo` | Alta de gasto fijo mensual |
| `/recurrentes` | `/fijos` | Lista los gastos fijos |
| `/borrarrecurrente <id>` | `/borrarfijo` | Elimina un gasto fijo |
| `/menu` | `/start`, `/ayuda`, `/help`, `/inicio` | Abre el menú guiado con botones |

> Los comandos siguen disponibles para quien quiera ir rápido, pero **no hace
> falta memorizarlos**: con `/start` se navega todo desde el menú con botones.

**Notas de uso**

- **Menú**: `/start` muestra las opciones agrupadas. Las acciones sin parámetros
  (ver resumen, gráficos, categorías, límites, fijos, últimos) se ejecutan al
  pulsar; las que requieren datos (registrar gasto, fijar límite…) muestran la
  sintaxis con un ejemplo.
- **Foto de ticket**: manda una foto al chat y el bot la lee y te propone el
  gasto con botones de **✅ Confirmar / 🏷️ Categoría / ❌ Cancelar**.
- Los importes admiten coma o punto decimal (`12,50` o `12.50`) y opcionalmente
  el símbolo `€`.
- Las categorías pueden tener varias palabras; el bot las reconoce al principio
  del texto y toma el resto como descripción.
- En `/recurrente`, el día puede ser de `1` a `31` (día fijo, ajustado al final
  del mes si no existe) o de `-1` a `-28` para contar desde el final del mes.

---

## 🧱 Stack y arquitectura

- **PHP 8.4+**, **Symfony 8.1**
- **Doctrine ORM** + **Doctrine Migrations** (MariaDB/MySQL)
- **symfony/http-client** para la Bot API de Telegram (sin librerías de terceros)
- **wkhtmltoimage** para renderizar los gráficos (Chart.js → PNG)
- **Google Gemini** (API REST de visión) para leer los tickets, detrás de una
  interfaz `ReceiptExtractorInterface` (motor intercambiable)
- Recepción de mensajes por **webhook** HTTPS (mensajes, fotos y pulsaciones de
  botones)

Flujo de un _update_ entrante:

```
Telegram → POST /telegram/webhook → TelegramWebhookController
        → UpdateProcessor (autoriza por whitelist)
            ├─ texto    → CommandRouter → BotCommand (lógica + persistencia)
            ├─ foto     → ReceiptFlow  → Gemini → PendingExpense + botones
            └─ callback → MenuCallbackHandler / PendingExpenseCallbackHandler
        → TelegramApi (sendMessage / sendPhoto / editMessageText)
```

Cada comando del bot implementa `BotCommandInterface` y se autoregistra mediante
un tag de Symfony, de modo que añadir un comando nuevo no requiere tocar la
configuración.

---

## ✅ Requisitos previos

En el servidor necesitas:

- **PHP 8.4 o superior** (CLI y para el servidor web) con las extensiones
  `pdo_mysql`, `ctype`, `iconv`, `mbstring` y `bcmath`.
- **Composer**
- **MariaDB** o **MySQL**
- Un **servidor web** con soporte HTTPS y un **dominio** con certificado TLS
  válido (Telegram exige HTTPS para el webhook).
- **wkhtmltoimage** (paquete `wkhtmltopdf`) para generar los gráficos. En Linux
  conviene usar la _build_ estática con Qt parcheado (renderiza sin servidor
  gráfico); si usas la versión del repositorio del sistema, ejecútalo bajo
  `xvfb-run`.

Y dos credenciales (gratuitas):

- Un **bot de Telegram** y su token, creados con [@BotFather](https://t.me/BotFather).
- Una **API key de Google Gemini** ([Google AI Studio](https://aistudio.google.com/apikey))
  para la lectura de tickets. El _free tier_ es suficiente para uso personal.
  Solo es necesaria si quieres usar la función de fotos.

---

## 🚀 Instalación en un servidor

> Los pasos son independientes del sistema operativo. Adapta las rutas y el
> usuario del servicio web a tu entorno.

1. **Sube el proyecto** al servidor (git, rsync, FTP… lo que prefieras).

2. **Instala las dependencias** en modo producción:

   ```bash
   composer install --no-dev --optimize-autoloader
   ```

3. **Configura el entorno** creando un archivo `.env.local` (ver el apartado
   siguiente).

4. **Crea la base de datos y aplica las migraciones**:

   ```bash
   php bin/console doctrine:database:create --if-not-exists
   php bin/console doctrine:migrations:migrate --no-interaction
   ```

5. **Genera y calienta la caché** de producción:

   ```bash
   php bin/console cache:clear
   php bin/console cache:warmup
   ```

6. **Apunta el servidor web** al directorio `public/` como _document root_
   (el _front controller_ es `public/index.php`). Asegúrate de que el usuario
   del servicio web pueda escribir en `var/`.

7. **Registra el webhook** y **da de alta a los usuarios** (apartados de abajo).

---

## ⚙️ Configuración (`.env.local`)

Crea un archivo `.env.local` en la raíz (no se versiona) con tus valores reales:

```dotenv
APP_ENV=prod
APP_DEBUG=0
APP_SECRET=genera-una-cadena-aleatoria

# Conexión a la base de datos
DATABASE_URL="mysql://USUARIO:PASSWORD@127.0.0.1:3306/gastos_bot?serverVersion=VERSION&charset=utf8mb4"

# Bot de Telegram
TELEGRAM_BOT_TOKEN="el-token-que-te-da-@BotFather"
TELEGRAM_WEBHOOK_SECRET="otra-cadena-aleatoria-larga"

# Gráficos: ruta al binario de wkhtmltoimage (en Linux suele bastar "wkhtmltoimage")
WKHTMLTOIMAGE_BIN="wkhtmltoimage"

# Lectura de tickets (Google Gemini). Solo si usas la función de fotos.
GEMINI_API_KEY="tu-api-key-de-google-ai-studio"
GEMINI_MODEL="gemini-2.5-flash"
```

- `VERSION` debe coincidir con tu servidor, p. ej. `10.11.2-MariaDB` o `8.0.21`
  (MySQL).
- `TELEGRAM_WEBHOOK_SECRET` es un secreto propio que Telegram reenvía en cada
  petición (cabecera `X-Telegram-Bot-Api-Secret-Token`); el bot rechaza
  cualquier petición que no lo incluya correctamente.
- Para generar cadenas aleatorias: `php -r "echo bin2hex(random_bytes(24));"`.
- `GEMINI_MODEL` por defecto es `gemini-2.5-flash` (multimodal y con _free tier_).
  Si ves errores de cuota `limit: 0`, prueba con otro modelo _flash_ disponible
  en tu cuenta/región.

---

## 🗄️ Base de datos y migraciones

El esquema se gestiona con Doctrine Migrations. Comandos habituales:

```bash
# Aplicar las migraciones pendientes
php bin/console doctrine:migrations:migrate --no-interaction

# Comprobar que el esquema coincide con las entidades
php bin/console doctrine:schema:validate
```

Si en el futuro modificas alguna entidad, genera una nueva migración con
`php bin/console make:migration` y vuelve a migrar.

---

## 🤖 Poner en marcha el bot de Telegram (paso a paso)

Una vez instalado el proyecto y servido por HTTPS, estos son los pasos
**específicos del bot**, en orden:

1. **Crea el bot con [@BotFather](https://t.me/BotFather)**
   - Escríbele `/newbot`, dale un nombre y un usuario (`..._bot`).
   - Copia el **token** que te da y ponlo en `.env.local` (`TELEGRAM_BOT_TOKEN`).
   - _(Opcional)_ Con `/setcommands` puedes registrar la lista de comandos para
     que Telegram los autocomplete.

2. **Configura el secreto del webhook** en `.env.local`
   (`TELEGRAM_WEBHOOK_SECRET`, una cadena aleatoria larga).

3. **Registra el webhook** apuntando a tu dominio:

   ```bash
   php bin/console app:bot:set-webhook https://TU_DOMINIO/telegram/webhook
   ```

   El comando valida el token, usa el secreto del `.env.local` y suscribe el bot
   a los _updates_ de **mensajes, fotos y botones** (`message`, `callback_query`).

4. **Date de alta como usuario autorizado.** Escríbele cualquier cosa al bot:
   como aún no estás en la lista, te responderá con **tu ID de Telegram**. Con
   ese ID:

   ```bash
   php bin/console app:user:add <tu-id> "Tu nombre"
   ```

   Repite para cada persona (p. ej. tu pareja).

5. **Listo.** Abre el chat y escribe **`/start`**: aparecerá el menú con botones.
   Prueba a mandar la **foto de un ticket** para registrar un gasto al instante.

> 💡 Para probar **sin desplegar**, expón tu entorno local con un túnel HTTPS
> (ngrok, _dev tunnels_ de VS Code…) y usa esa URL en `app:bot:set-webhook`.
> Ten en cuenta que la URL del túnel suele cambiar al reiniciarlo: re-registra el
> webhook con la nueva URL cuando eso ocurra.

Los dos apartados siguientes detallan el webhook y la lista de usuarios.

---

## 🔗 Registrar el webhook de Telegram

Con el sitio ya servido por HTTPS, indica a Telegram dónde enviar los mensajes:

```bash
php bin/console app:bot:set-webhook https://TU_DOMINIO/telegram/webhook
```

El comando comprueba primero que el token es válido (`getMe`) y usa el
`TELEGRAM_WEBHOOK_SECRET` del `.env.local`. Como Telegram solo admite un webhook
por bot, ejecutarlo de nuevo **sobrescribe** el anterior.

Para comprobar el estado o eliminarlo:

```bash
# Estado actual del webhook
curl "https://api.telegram.org/bot<TOKEN>/getWebhookInfo"

# Eliminar el webhook
php bin/console app:bot:set-webhook --delete
```

---

## 👥 Usuarios autorizados (whitelist)

El bot solo responde a IDs de Telegram dados de alta. Para descubrir tu ID,
escríbele al bot: al no estar autorizado, te responderá con tu propio ID.

```bash
php bin/console app:user:add <telegram-id> "Nombre"
```

Ejecuta el comando para cada persona que vaya a usar el bot. Volver a ejecutarlo
con un ID existente actualiza el nombre y reactiva al usuario.

---

## ⏰ Tareas programadas (cron)

Dos procesos están pensados para ejecutarse de forma periódica con el cron del
sistema. Configúralos (p. ej. con `crontab -e`) usando rutas absolutas:

```cron
# Generar los gastos recurrentes que toquen — cada día a las 6:00
0 6 * * *   cd /ruta/al/proyecto && php bin/console app:recurring:run >> var/log/cron.log 2>&1

# Resumen semanal — domingos a las 20:00 (mes actual)
0 20 * * 0  cd /ruta/al/proyecto && php bin/console app:summary:send >> var/log/cron.log 2>&1

# Cierre de mes — día 1 a las 9:30 (resumen del mes anterior)
30 9 1 * *  cd /ruta/al/proyecto && php bin/console app:summary:send pasado >> var/log/cron.log 2>&1
```

`app:recurring:run` es **idempotente**: aunque se ejecute varias veces el mismo
día, cada gasto recurrente se genera una sola vez por mes.

---

## 🛠️ Comandos de consola

| Comando | Descripción |
|---|---|
| `app:user:add <id> <nombre>` | Autoriza (o reactiva) a un usuario |
| `app:bot:set-webhook <url>` / `--delete` | Registra o elimina el webhook |
| `app:bot:simulate <id> <mensaje>` | Prueba un mensaje en local sin Telegram |
| `app:recurring:run` | Crea los gastos recurrentes pendientes de hoy |
| `app:summary:send [actual\|pasado\|MM/AAAA]` | Envía el informe (texto + gráficos) a todos los usuarios |
| `app:chart:preview <tipo> [salida] [mes]` | Renderiza un gráfico a un PNG local (sin enviarlo) |

---

## 🧩 Modelo de datos

| Entidad | Descripción |
|---|---|
| `TelegramUser` | Usuario autorizado (whitelist): `telegramId`, nombre, activo |
| `Category` | Categoría de gasto |
| `Expense` | Gasto: importe, categoría, usuario, fecha y descripción |
| `CategoryBudget` | Límite mensual por categoría con `effectiveFrom` (histórico) |
| `RecurringExpense` | Plantilla de gasto fijo: importe, categoría, día del mes |
| `PendingExpense` | Gasto extraído de un ticket a la espera de confirmación |

El límite vigente de una categoría para un mes es la fila de `CategoryBudget`
con el `effectiveFrom` más reciente anterior o igual a ese mes.

---

## 💻 Desarrollo local y pruebas

Para probar los comandos **sin pasar por Telegram**, usa el simulador (debe
existir el usuario indicado):

```bash
php bin/console app:user:add 123456 "Pruebas"
php bin/console app:bot:simulate 123456 /gasto 12,50 Comida menú
php bin/console app:bot:simulate 123456 /resumen
```

Para probar el bot real contra tu entorno local sin desplegar, puedes exponer el
servidor con un túnel HTTPS (ngrok, dev tunnels de VS Code, etc.) y apuntar el
webhook a la URL del túnel con `app:bot:set-webhook`.

---

## 📁 Estructura del proyecto

```
src/
├── Command/                 Comandos de consola (app:*)
├── Controller/
│   └── TelegramWebhookController.php   Endpoint del webhook
├── Entity/                  Entidades Doctrine
├── Repository/              Repositorios y consultas
├── Service/
│   ├── Notifier.php                 Difusión de mensajes a los usuarios
│   ├── SummaryBuilder.php           Construcción del texto de resumen
│   ├── SummaryReporter.php          Informe (texto + gráficos) a uno o todos
│   ├── RecurringExpenseRunner.php   Generación de gastos recurrentes
│   ├── Chart/                       Fábrica y render de gráficos (wkhtmltoimage)
│   ├── Receipt/                     Extracción de tickets (interfaz + Gemini)
│   └── Telegram/TelegramApi.php     Cliente de la Bot API
└── Telegram/
    ├── CommandRouter.php    Parseo y enrutado de comandos de texto
    ├── UpdateProcessor.php  Autorización y despacho (texto / foto / botón)
    ├── CategoryMatcher.php  Reconocimiento de categorías
    ├── Command/             Un archivo por comando del bot
    ├── Menu/                Menú guiado y sus pulsaciones
    ├── Receipt/             Flujo de fotos de tickets y su confirmación
    └── Util/                Utilidades (importes, meses)
```

---

## 🔒 Seguridad

- **Whitelist**: solo los `telegramId` dados de alta pueden interactuar.
- **Secreto de webhook**: se valida la cabecera `X-Telegram-Bot-Api-Secret-Token`
  con comparación segura; las peticiones sin el secreto correcto reciben `403`.
- **Secretos fuera del repositorio**: el token del bot, la conexión a la base de
  datos y el secreto del webhook viven en `.env.local`, que está en `.gitignore`.

---

## 🗺️ Roadmap

Funciones contempladas para más adelante:

- Exportación de gastos a CSV/Excel.
- Registro de **ingresos** y balance neto del mes.
- Desglose por persona (cuánto aporta cada usuario).
- Guardar la imagen del ticket junto al gasto registrado.
- Presupuesto total mensual y objetivo de ahorro.

---

## 📄 Licencia

Proyecto privado de uso personal. Todos los derechos reservados.
