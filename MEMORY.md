# MEMORY.md — Backend

## Decisiones de Arquitectura

- Laravel 12 + MySQL + Redis + Horizon
- Arquitectura modular por dominio (`app/Domain/`)
- Sanctum para auth API
- Fingerprint de dispositivo para una cuenta por dispositivo
- Registro solo con nick, sin email

## Estado Actual

- [x] Esqueleto Laravel 12 y carpetas `Domain/`, `Api/V1`, `Admin`
- [x] Sanctum 4.3, Horizon 5.50 y Pest 3.8
- [x] Registro, ingreso y salida con nick y fingerprint de dispositivo
- [x] MySQL en el `.env` de trabajo (`politicav2_db`). Las pruebas siguen en SQLite. Redis de producción sigue pendiente en local.
- [ ] Fase 1 frontend: pantallas de registro e ingreso
- [ ] Fase 1 admin: ver usuarios
- [ ] Fase 2: Debates + argumentos
- [ ] Fase 3: Noticias + moderación
- [ ] Fase 4: Métricas + API admin

## Endpoints Implementados

- `GET /api/v1/ping` — sonda pública, sin datos de usuario
- `POST /api/v1/auth/register` — nick, consentimiento y header `X-Device-Fingerprint`
- `POST /api/v1/auth/login` — el mismo dispositivo
- `POST /api/v1/auth/logout` — revoca el token Sanctum

## Problemas Conocidos

- El PATH de Cursor apunta a PHP 8.1.10. Laragon tiene PHP 8.2.28 en `E:\laragon\bin\php\php-8.2.28-Win32-vs16-x64\php.exe`. Artisan, Composer y Pest se ejecutan con ese binario.
- Horizon exige `ext-pcntl` y `ext-posix`, que no existen en Windows. El paquete está instalado para Linux; en local la cola sigue con `queue:work`.
- El `.env` de trabajo usa MySQL de Laragon. No hace falta PostgreSQL.

## Notas de Implementación

- El fingerprint canónico es un SHA-256 de señales de servidor más el hash del cliente. Ese valor es el que se guarda y el que se compara. No se compara el valor en claro con el hash.
- Una cuenta por dispositivo: `device_fingerprint` UNIQUE.
- Si la persona pierde el dispositivo, la recuperación es manual desde el admin.
