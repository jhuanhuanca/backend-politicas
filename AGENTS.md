# AGENTS.md — Backend Política

## Contexto del Proyecto

API backend para plataforma de deliberación ciudadana.
Stack: Laravel 12, PHP 8.3+, MySQL, Redis, Sanctum, Horizon.

## Estructura de Directorios

```
app/
├── Domain/              # Lógica de negocio modular
│   ├── Identity/        # Usuarios, dispositivos, sesiones
│   ├── Debates/         # Temas, argumentos, votos
│   ├── News/            # Ingesta de noticias
│   ├── Moderation/      # Reportes, sanciones
│   └── Metrics/         # Scores y estadísticas
├── Http/
│   ├── Controllers/Api/V1/
│   └── Controllers/Admin/
├── Jobs/
├── Policies/
└── Support/
```

## Convenciones de Código

- PHP 8.3 con readonly properties y constructor promotion
- PSR-12 estricto
- Service classes para lógica de negocio, no en controladores
- Form Requests para validación
- API Resources para transformación de respuestas
- Repositorios solo cuando hay lógica compleja de consulta

## Reglas Críticas

1. NUNCA exponer datos de usuarios en endpoints públicos
2. SIEMPRE validar payloads con FormRequest
3. SIEMPRE usar eager loading para evitar N+1
4. NUNCA usar `DB::raw()` sin sanitizar
5. SIEMPRE indexar columnas usadas en WHERE y ORDER BY
6. SIEMPRE usar transacciones para operaciones multi-tabla
7. NUNCA modificar migraciones existentes en producción

## Autenticación

- Sanctum para tokens API
- Fingerprint de dispositivo obligatorio (ver Identity/DeviceFingerprint)
- Registro solo con nick (sin email)
- Una cuenta por dispositivo (`device_fingerprint` único)
- Middleware `EnsureSingleDevice` en rutas de auth

## API Design

- RESTful con versionado en URL (`/api/v1/`)
- Respuestas consistentes: `{ data, meta, errors }`
- Paginación cursor-based para feeds
- Rate limiting por dispositivo y por IP
- Endpoints admin separados: `/api/admin/*`

## Testing

- Pest para tests
- Feature tests para endpoints
- Unit tests para Actions y Services
- Coverage mínimo 80% en `Domain/`

## Comandos Frecuentes

```
php artisan test
php artisan migrate:fresh --seed
php artisan horizon
composer run pint
```
