<?php
/**
 * Tests de validación de datos de negocio (no requieren conexión a BD).
 */

use PHPUnit\Framework\TestCase;

// Cargar process_business sin session ni headers (TESTING=true evita efectos secundarios)
if (!function_exists('validateBusinessData')) {
    // stub getDbConnection para que no intente conectarse (solo si aún no está definida)
    if (!function_exists('getDbConnection')) {
        function getDbConnection() { return null; }
    }
    require_once __DIR__ . '/../business/process_business.php';
}

class BusinessValidationTest extends TestCase
{
    private function validData(): array
    {
        return [
            'name'          => 'Panadería La Buena',
            'address'       => 'Av. Corrientes 1234',
            'business_type' => 'comercio',
            'lat'           => '-34.6037',
            'lng'           => '-58.3816',
            'phone'         => '+54 11 4000-0000',
            'email'         => 'info@panaderia.com',
            'website'       => 'https://panaderia.com',
            'price_range'   => '2',
        ];
    }

    public function testValidDataPassesValidation(): void
    {
        $result = validateBusinessData($this->validData());
        $this->assertTrue($result['valid'], implode(', ', $result['errors']));
    }

    public function testMissingNameFails(): void
    {
        $data         = $this->validData();
        $data['name'] = '';
        $result       = validateBusinessData($data);
        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
    }

    public function testMissingAddressFails(): void
    {
        $data            = $this->validData();
        $data['address'] = '';
        $result          = validateBusinessData($data);
        $this->assertFalse($result['valid']);
    }

    public function testEmptyBusinessTypeFails(): void
    {
        $data                  = $this->validData();
        $data['business_type'] = '';
        $result                = validateBusinessData($data);
        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
    }

    public function testUnknownBusinessTypeNormalizesToOtros(): void
    {
        $data                  = $this->validData();
        $data['business_type'] = 'fonoaudiologia';
        $result                = validateBusinessData($data);
        $this->assertTrue($result['valid'], implode(', ', $result['errors']));
        $this->assertEquals('otros', $result['data']['business_type']);
    }

    public function testInvalidLatitudeFails(): void
    {
        $data        = $this->validData();
        $data['lat'] = '200';
        $result      = validateBusinessData($data);
        $this->assertFalse($result['valid']);
    }

    public function testInvalidLongitudeFails(): void
    {
        $data        = $this->validData();
        $data['lng'] = '-200';
        $result      = validateBusinessData($data);
        $this->assertFalse($result['valid']);
    }

    public function testInvalidEmailFails(): void
    {
        $data          = $this->validData();
        $data['email'] = 'not-an-email';
        $result        = validateBusinessData($data);
        $this->assertFalse($result['valid']);
    }

    public function testInvalidWebsiteFails(): void
    {
        $data            = $this->validData();
        $data['website'] = 'not a url';
        $result          = validateBusinessData($data);
        $this->assertFalse($result['valid']);
    }

    public function testPriceRangeIsClampedToDefaults(): void
    {
        $data                = $this->validData();
        $data['price_range'] = '99';
        $result              = validateBusinessData($data);
        $this->assertTrue($result['valid']);
        $this->assertEquals(3, $result['data']['price_range']);
    }

    public function testComercioFieldsAreIncludedForComercioType(): void
    {
        $data                         = $this->validData();
        $data['tipo_comercio']        = 'Panadería';
        $data['horario_apertura']     = '08:00';
        $data['horario_cierre']       = '20:00';
        $data['dias_cierre']          = 'domingo';
        $data['categorias_productos'] = 'pan,facturas';

        $result = validateBusinessData($data);
        $this->assertTrue($result['valid']);
        $this->assertEquals('Panadería', $result['data']['tipo_comercio']);
    }

    public function testComercioFieldsAreNotIncludedForNonComercio(): void
    {
        $data                  = $this->validData();
        $data['business_type'] = 'hotel';
        $result                = validateBusinessData($data);
        $this->assertTrue($result['valid']);
        $this->assertArrayNotHasKey('tipo_comercio', $result['data']);
    }

    public function testPhoneWithInvalidCharsIsRejected(): void
    {
        $data          = $this->validData();
        $data['phone'] = 'DROP TABLE;';
        $result        = validateBusinessData($data);
        $this->assertFalse($result['valid']);
    }

    public function testEmptyOptionalFieldsAreNullified(): void
    {
        $data            = $this->validData();
        $data['phone']   = '';
        $data['email']   = '';
        $data['website'] = '';
        $result          = validateBusinessData($data);
        $this->assertTrue($result['valid']);
        $this->assertNull($result['data']['phone']);
        $this->assertNull($result['data']['email']);
        $this->assertNull($result['data']['website']);
    }
}
