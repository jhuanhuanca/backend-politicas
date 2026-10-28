---
name: device-fingerprint
description: Implementa el fingerprint híbrido de dispositivo para el registro de una cuenta por nick. Usar al registrar usuarios, validar dispositivos, el header X-Device-Fingerprint o la recuperación manual de un dispositivo.
---

# Fingerprint de dispositivo

## Cuándo usar

Al registrar o validar un dispositivo en el backend o en el cliente.

Hay un solo hash canónico. Se persiste ese hash y la unicidad se comprueba contra ese mismo valor. No se compara el fingerprint en claro con el hash guardado.

## Pasos backend

1. El cliente envía el header `X-Device-Fingerprint`.
2. El servicio arma señales de servidor: user agent, `Accept-Language`, `Sec-CH-UA-Platform` e IP.
3. Combina esas señales con el hash del cliente y calcula SHA-256.
4. Busca ese hash en `users.device_fingerprint`.
5. Si existe, rechaza el registro.
6. Si no existe, crea el usuario dentro de una transacción y guarda ese hash.

```php
public function generate(Request $request): string
{
    $serverSignals = implode('|', [
        (string) $request->userAgent(),
        (string) $request->header('Accept-Language'),
        (string) $request->header('Sec-CH-UA-Platform'),
        (string) $request->ip(),
    ]);

    $clientHash = (string) $request->header('X-Device-Fingerprint', '');

    return hash('sha256', $serverSignals.'|'.$clientHash);
}
```

Registro: nick, consentimiento de términos obligatorio, consentimiento de métricas opcional. El nick cumple `^[a-zA-Z0-9_]+$`, entre 3 y 20 caracteres.

## Pasos frontend

1. Generar el fingerprint con FingerprintJS o el equivalente ya elegido.
2. Guardarlo en `localStorage` bajo `device-id`.
3. Enviarlo en `X-Device-Fingerprint` en cada request. El cliente axios ya lo hace si la clave existe.
4. En el registro, incluir el fingerprint.

## Consideraciones

| Situación | Qué pasa |
| --- | --- |
| Cambia de navegador | El fingerprint cambia. Hace falta recuperación manual. |
| Borra cookies | El canvas puede persistir y repetir el mismo fingerprint. |
| Usa VPN | La IP cambia. El hash de navegador sigue. Por eso la IP no puede ser la única señal. |
| Comparte el dispositivo | El segundo registro se rechaza. |
| Pierde el dispositivo | El admin resetea `device_fingerprint` tras verificar identidad e historial. |

La IP sola no identifica a una persona. El proceso de recuperación no se automatiza.
