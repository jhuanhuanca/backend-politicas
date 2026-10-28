---
name: laravel-api-endpoint
description: Crea un endpoint REST en Laravel 12 con FormRequest, API Resource, Action de dominio, controlador en Api/V1, ruta /api/v1 y feature test Pest. Usar al añadir endpoints, recursos o acciones de la API pública o admin.
---

# Crear endpoint API Laravel

## Cuándo usar

Cuando haya que crear un endpoint REST en `backend-politica`.

## Pasos

1. Crear FormRequest en `app/Http/Requests/`.
2. Crear API Resource en `app/Http/Resources/`.
3. Crear Action en `app/Domain/{Modulo}/Actions/`.
4. Crear Controller en `app/Http/Controllers/Api/V1/` (o `Admin/` si es `/api/admin`).
5. Registrar la ruta en `routes/api.php`.
6. Crear el feature test con Pest.

La respuesta es `{ data, meta, errors }`. El controlador no contiene reglas de negocio. Eager load si hay relaciones. Transacción si escribe en más de una tabla. No expongas el usuario ni el fingerprint.

## Ejemplo

`app/Http/Requests/StoreTopicRequest.php`

```php
public function rules(): array
{
    return [
        'title' => ['required', 'string', 'min:8', 'max:160'],
        'body' => ['required', 'string', 'max:5000'],
    ];
}
```

`app/Domain/Debates/Actions/CreateTopicAction.php`

```php
public function execute(User $author, array $data): Topic
{
    return DB::transaction(function () use ($author, $data): Topic {
        return Topic::query()->create([
            'user_id' => $author->id,
            'title' => $data['title'],
            'body' => $data['body'],
        ]);
    });
}
```

`app/Http/Resources/TopicResource.php`

```php
public function toArray(Request $request): array
{
    return [
        'id' => $this->id,
        'title' => $this->title,
        'body' => $this->body,
        'created_at' => $this->created_at,
    ];
}
```

`app/Http/Controllers/Api/V1/TopicController.php`

```php
public function store(StoreTopicRequest $request, CreateTopicAction $action): JsonResponse
{
    $topic = $action->execute($request->user(), $request->validated());

    return (new TopicResource($topic))
        ->response()
        ->setStatusCode(201);
}
```

`routes/api.php`, dentro del grupo `v1` y con `auth:sanctum` cuando Sanctum exista:

```php
Route::post('/topics', [TopicController::class, 'store']);
```

Test Pest:

```php
it('creates a topic', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/topics', [
            'title' => 'Presupuesto del barrio',
            'body' => 'Hay que discutir las prioridades.',
        ])
        ->assertCreated()
        ->assertJsonPath('data.title', 'Presupuesto del barrio');
});
```
