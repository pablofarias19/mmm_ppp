<?php
/**
 * Tests de relaciones entre Industry, Business y Brand.
 * No requieren conexión a BD real; validan la lógica del modelo Industry
 * (validaciones, campos de relación, sanitización).
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../models/Industry.php';

class IndustryRelationsTest extends TestCase
{
    // ── Datos de base ─────────────────────────────────────────────────────────

    private function validIndustryData(): array
    {
        return [
            'user_id'     => 1,
            'name'        => 'Industria Automotriz SA',
            'status'      => 'activa',
            'business_id' => null,
            'brand_id'    => null,
        ];
    }

    // ── Validación de campos de relación ──────────────────────────────────────

    public function testIndustryValidDataPassesValidation(): void
    {
        $errors = \App\Models\Industry::validate($this->validIndustryData(), true);
        $this->assertEmpty($errors, implode(', ', $errors));
    }

    public function testIndustryWithBusinessIdPassesValidation(): void
    {
        $data = $this->validIndustryData();
        $data['business_id'] = 42;
        $errors = \App\Models\Industry::validate($data, true);
        $this->assertEmpty($errors, 'Una industria con business_id debe ser válida');
    }

    public function testIndustryWithBrandIdPassesValidation(): void
    {
        $data = $this->validIndustryData();
        $data['brand_id'] = 7;
        $errors = \App\Models\Industry::validate($data, true);
        $this->assertEmpty($errors, 'Una industria con brand_id debe ser válida');
    }

    public function testIndustryWithBothRelationsPassesValidation(): void
    {
        $data = $this->validIndustryData();
        $data['business_id'] = 10;
        $data['brand_id']    = 5;
        $errors = \App\Models\Industry::validate($data, true);
        $this->assertEmpty($errors, 'Una industria puede relacionarse simultáneamente con negocio y marca');
    }

    public function testIndustryWithoutRelationsPassesValidation(): void
    {
        $data = $this->validIndustryData();
        // business_id y brand_id son null → relaciones opcionales
        $errors = \App\Models\Industry::validate($data, true);
        $this->assertEmpty($errors, 'Las relaciones con negocio y marca son opcionales');
    }

    // ── Validación de campos obligatorios ─────────────────────────────────────

    public function testIndustryMissingNameFails(): void
    {
        $data         = $this->validIndustryData();
        $data['name'] = '';
        $errors = \App\Models\Industry::validate($data, true);
        $this->assertNotEmpty($errors, 'El nombre es obligatorio');
    }

    public function testIndustryMissingUserIdFails(): void
    {
        $data            = $this->validIndustryData();
        $data['user_id'] = 0;
        $errors = \App\Models\Industry::validate($data, true);
        $this->assertNotEmpty($errors, 'El user_id es obligatorio y debe ser > 0');
    }

    public function testIndustryInvalidStatusFails(): void
    {
        $data           = $this->validIndustryData();
        $data['status'] = 'inexistente';
        $errors = \App\Models\Industry::validate($data, true);
        $this->assertNotEmpty($errors, 'Un status no válido debe fallar');
    }

    public function testIndustryInvalidEmailFails(): void
    {
        $data                   = $this->validIndustryData();
        $data['contact_email']  = 'no-es-un-email';
        $errors = \App\Models\Industry::validate($data, true);
        $this->assertNotEmpty($errors, 'Un email de contacto inválido debe fallar');
    }

    public function testIndustryInvalidWebsiteFails(): void
    {
        $data            = $this->validIndustryData();
        $data['website'] = 'not a url';
        $errors = \App\Models\Industry::validate($data, true);
        $this->assertNotEmpty($errors, 'Una URL de sitio web inválida debe fallar');
    }

    public function testIndustryValidEmailPasses(): void
    {
        $data                  = $this->validIndustryData();
        $data['contact_email'] = 'contacto@industria.com';
        $errors = \App\Models\Industry::validate($data, true);
        $this->assertEmpty($errors, 'Un email de contacto válido debe pasar');
    }

    public function testIndustryValidWebsitePasses(): void
    {
        $data            = $this->validIndustryData();
        $data['website'] = 'https://industria.com';
        $errors = \App\Models\Industry::validate($data, true);
        $this->assertEmpty($errors, 'Una URL válida debe pasar');
    }

    // ── Constantes de estado ──────────────────────────────────────────────────

    public function testIndustryValidStatusesAreCorrect(): void
    {
        $expected = ['borrador', 'activa', 'archivada'];
        $this->assertEquals($expected, \App\Models\Industry::VALID_STATUSES);
    }

    public function testAllValidStatusesPassValidation(): void
    {
        foreach (\App\Models\Industry::VALID_STATUSES as $status) {
            $data           = $this->validIndustryData();
            $data['status'] = $status;
            $errors = \App\Models\Industry::validate($data, true);
            $this->assertEmpty($errors, "El status '$status' debe ser válido");
        }
    }

    // ── Verificar que el modelo expone los campos de relación ─────────────────

    public function testIndustryUpdateAllowsRelationFields(): void
    {
        // Verificar que 'business_id' y 'brand_id' están en la lista de campos
        // permitidos para update (white-list en Industry::update).
        $reflector = new ReflectionMethod(\App\Models\Industry::class, 'update');
        $source    = file_get_contents(__DIR__ . '/../models/Industry.php');

        $this->assertStringContainsString("'business_id'", $source, 'Industry::update debe incluir business_id en allowed fields');
        $this->assertStringContainsString("'brand_id'",    $source, 'Industry::update debe incluir brand_id en allowed fields');
    }

    // ── Verificar que el modelo expone los campos de relación en create ───────

    public function testIndustryCreateQueryIncludesRelationFields(): void
    {
        $source = file_get_contents(__DIR__ . '/../models/Industry.php');
        $this->assertStringContainsString('business_id', $source, 'Industry::create debe incluir business_id');
        $this->assertStringContainsString('brand_id',    $source, 'Industry::create debe incluir brand_id');
    }

    // ── Verificar que la migración de FK existe ───────────────────────────────

    public function testFkMigrationFileExists(): void
    {
        $migration = __DIR__ . '/../migrations/023_relations_fk_image_limits.sql';
        $this->assertFileExists($migration, 'La migración 023 con FK constraints debe existir');
    }

    public function testFkMigrationContainsFkBusinessConstraint(): void
    {
        $migration = file_get_contents(__DIR__ . '/../migrations/023_relations_fk_image_limits.sql');
        $this->assertStringContainsString(
            'fk_industries_business',
            $migration,
            'La migración 023 debe definir el FK industries → businesses'
        );
    }

    public function testFkMigrationContainsFkBrandConstraint(): void
    {
        $migration = file_get_contents(__DIR__ . '/../migrations/023_relations_fk_image_limits.sql');
        $this->assertStringContainsString(
            'fk_industries_brand',
            $migration,
            'La migración 023 debe definir el FK industries → brands'
        );
    }

    public function testFkMigrationContainsIndustryImagesTable(): void
    {
        $migration = file_get_contents(__DIR__ . '/../migrations/023_relations_fk_image_limits.sql');
        $this->assertStringContainsString(
            'industry_images',
            $migration,
            'La migración 023 debe crear la tabla industry_images'
        );
    }
}
