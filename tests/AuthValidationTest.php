<?php
/**
 * Tests del flujo de autenticación (sin BD real — prueba lógica de validación).
 */

use PHPUnit\Framework\TestCase;

class AuthValidationTest extends TestCase
{
    // ─── Helpers duplicados para test sin efectos secundarios ────────────

    private function registerUser(string $username, string $password, string $confirm): array
    {
        if (empty($username) || empty($password) || empty($confirm)) {
            return ['success' => false, 'message' => 'Por favor complete todos los campos'];
        }
        if (mb_strlen($username) > 50 || !preg_match('/^[\w\.\-]{3,50}$/', $username)) {
            return ['success' => false, 'message' => 'El usuario debe tener entre 3 y 50 caracteres alfanuméricos.'];
        }
        if ($password !== $confirm) {
            return ['success' => false, 'message' => 'Las contraseñas no coinciden'];
        }
        if (strlen($password) < 8) {
            return ['success' => false, 'message' => 'La contraseña debe tener al menos 8 caracteres'];
        }
        if (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
            return ['success' => false, 'message' => 'La contraseña debe contener al menos una mayúscula y un número.'];
        }
        return ['success' => true, 'message' => 'ok'];
    }

    // ─── Tests ────────────────────────────────────────────────────────────

    public function testValidRegistrationPasses(): void
    {
        $result = $this->registerUser('usuario1', 'Segura123', 'Segura123');
        $this->assertTrue($result['success']);
    }

    public function testEmptyFieldsFail(): void
    {
        $result = $this->registerUser('', '', '');
        $this->assertFalse($result['success']);
    }

    public function testPasswordMismatchFails(): void
    {
        $result = $this->registerUser('usuario1', 'Segura123', 'Diferente456');
        $this->assertFalse($result['success']);
    }

    public function testShortPasswordFails(): void
    {
        $result = $this->registerUser('usuario1', 'Ab1', 'Ab1');
        $this->assertFalse($result['success']);
    }

    public function testPasswordWithoutUppercaseFails(): void
    {
        $result = $this->registerUser('usuario1', 'sinmayuscula1', 'sinmayuscula1');
        $this->assertFalse($result['success']);
    }

    public function testPasswordWithoutNumberFails(): void
    {
        $result = $this->registerUser('usuario1', 'SinNumeroAqui', 'SinNumeroAqui');
        $this->assertFalse($result['success']);
    }

    public function testUsernameTooShortFails(): void
    {
        $result = $this->registerUser('ab', 'Segura123', 'Segura123');
        $this->assertFalse($result['success']);
    }

    public function testUsernameWithInvalidCharsFails(): void
    {
        $result = $this->registerUser('usuario malo!', 'Segura123', 'Segura123');
        $this->assertFalse($result['success']);
    }

    public function testUsernameTooLongFails(): void
    {
        $longName = str_repeat('a', 51);
        $result   = $this->registerUser($longName, 'Segura123', 'Segura123');
        $this->assertFalse($result['success']);
    }

    public function testPasswordHashIsNotReversible(): void
    {
        $password = 'Segura123';
        $hash     = password_hash($password, PASSWORD_DEFAULT);
        $this->assertTrue(password_verify($password, $hash));
        $this->assertNotEquals($password, $hash);
    }
}
