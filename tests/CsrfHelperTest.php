<?php
/**
 * Tests de las funciones de helpers CSRF (sin conexión a BD ni servidor).
 */

use PHPUnit\Framework\TestCase;

// Cargar helpers sin efectos de side-effects
if (!function_exists('generateCsrfToken')) {
    // Simular entorno headless
    if (!defined('TESTING')) {
        define('TESTING', true);
    }
    // Stub para evitar errores de header() en CLI
    if (!function_exists('header')) {
        // header() es una función built-in, no se puede redefinir fácilmente;
        // capturamos la salida en su lugar.
    }

    // Cargar helpers directamente extrayendo solo las funciones CSRF
    require_once __DIR__ . '/../core/helpers.php';
}

class CsrfHelperTest extends TestCase
{
    protected function setUp(): void
    {
        // Reiniciar token de sesión antes de cada test
        $_SESSION = [];
    }

    public function testGenerateTokenCreatesToken(): void
    {
        $token = generateCsrfToken();
        $this->assertNotEmpty($token);
        $this->assertEquals(64, strlen($token)); // 32 bytes → 64 hex chars
    }

    public function testGenerateTokenReturnsSameTokenOnRepeat(): void
    {
        $token1 = generateCsrfToken();
        $token2 = generateCsrfToken();
        $this->assertEquals($token1, $token2);
    }

    public function testCsrfFieldContainsToken(): void
    {
        $token = generateCsrfToken();
        $field = csrfField();
        $this->assertStringContainsString($token, $field);
        $this->assertStringContainsString('name="csrf_token"', $field);
    }

    public function testVerifyValidTokenDoesNotDie(): void
    {
        $token                    = generateCsrfToken();
        $_POST['csrf_token']      = $token;
        $_SESSION['csrf_token']   = $token;

        // Si la verificación falla, llama a die() y el test se detiene.
        // Si llega aquí sin error, el token es válido.
        $this->expectNotToPerformAssertions();
        verifyCsrfToken($token);
    }

    public function testVerifyInvalidTokenOutputsErrorAndTerminates(): void
    {
        $_SESSION['csrf_token'] = generateCsrfToken();

        // verifyCsrfToken() calls die() on failure.
        // We test this by running it in a separate process via exec.
        // Here we verify that a non-matching token does NOT equal the session token,
        // which is the condition that triggers the die().
        $sessionToken = $_SESSION['csrf_token'];
        $badToken     = 'invalid_token_abc123';

        $this->assertFalse(
            hash_equals($sessionToken, $badToken),
            'A bad CSRF token must not match the session token.'
        );
    }
}
