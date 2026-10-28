<?php

namespace App\Console\Commands;

use App\Models\Staff;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateStaffCommand extends Command
{
    protected $signature = 'staff:create';

    protected $description = 'Crea la cuenta de staff del panel desde la terminal';

    public function handle(): int
    {
        $name = trim((string) $this->ask('Nombre', 'Mesa de moderación'));
        $email = strtolower(trim((string) $this->ask('Correo')));
        $password = (string) $this->secret('Contraseña');
        $confirmation = (string) $this->secret('Confirma la contraseña');

        $validator = Validator::make(
            [
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'password_confirmation' => $confirmation,
            ],
            [
                'name' => ['required', 'string', 'min:2', 'max:80'],
                'email' => ['required', 'string', 'email', 'max:160'],
                'password' => ['required', 'string', 'min:8', 'max:80', 'confirmed'],
            ],
            [
                'name.required' => 'Escribe el nombre.',
                'name.min' => 'El nombre tiene al menos 2 caracteres.',
                'email.required' => 'Escribe el correo de staff.',
                'email.email' => 'El correo no es válido.',
                'password.required' => 'Escribe la clave.',
                'password.min' => 'La clave tiene al menos 8 caracteres.',
                'password.confirmed' => 'La confirmación de la clave no coincide.',
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        if (Staff::query()->where('email', $email)->exists()) {
            $this->error('Ese correo ya tiene una cuenta de staff.');

            return self::FAILURE;
        }

        Staff::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => 'moderator',
        ]);

        $this->info('Cuenta de staff creada para '.$email.'.');

        return self::SUCCESS;
    }
}
