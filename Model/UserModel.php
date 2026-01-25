<?php

declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

class UserModel extends BaseModel
{
    private const ROLE_OPTIONS = ['admin', 'client'];

    public function getRoleOptions(): array
    {
        return self::ROLE_OPTIONS;
    }

    public function normalizeRegistrationInput(array $input): array
    {
        return [
            'name' => trim((string)($input['name'] ?? '')),
            'email' => strtolower(trim((string)($input['email'] ?? ''))),
            'password' => (string)($input['password'] ?? ''),
            'password_confirmation' => (string)($input['password_confirmation'] ?? ''),
        ];
    }

    public function validateRegistration(array $data): array
    {
        $errors = [];

        if ($data['name'] === '') {
            $errors['name'] = 'El nombre es obligatorio.';
        }

        if ($data['email'] === '' || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Ingresá un correo válido.';
        }

        if (strlen($data['password']) < 8) {
            $errors['password'] = 'La contraseña debe tener al menos 8 caracteres.';
        }

        if ($data['password'] !== $data['password_confirmation']) {
            $errors['password_confirmation'] = 'Las contraseñas no coinciden.';
        }

        return $errors;
    }

    public function emailExists(string $email): bool
    {
        $statement = $this->pdo->prepare('SELECT COUNT(*) FROM `users` WHERE `email` = :email');
        $statement->execute(['email' => $email]);
        return (int) $statement->fetchColumn() > 0;
    }

    public function create(array $data, string $role = 'client'): int
    {
        $roleValue = in_array($role, self::ROLE_OPTIONS, true) ? $role : 'client';

        $statement = $this->pdo->prepare(
            'INSERT INTO `users` (name, email, password_hash, role) VALUES (:name, :email, :password_hash, :role)'
        );

        $statement->execute([
            'name' => $data['name'],
            'email' => $data['email'],
            'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            'role' => $roleValue,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function attemptLogin(string $identifier, string $password): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM `users` WHERE `email` = :identifier OR `name` = :identifier LIMIT 1'
        );
        $statement->execute(['identifier' => $identifier]);
        $user = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, (string) $user['password_hash'])) {
            return null;
        }

        return $user;
    }
}
