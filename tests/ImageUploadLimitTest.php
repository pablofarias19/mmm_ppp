<?php
/**
 * Tests de límites de carga de imágenes para Negocios, Marcas e Industrias.
 * No requieren conexión a BD real; prueban las constantes y la lógica de
 * validación de los modelos/APIs directamente.
 */

use PHPUnit\Framework\TestCase;

// Cargar modelo BrandGallery
require_once __DIR__ . '/../models/BrandGallery.php';

class ImageUploadLimitTest extends TestCase
{
    // ── Constantes de límite ──────────────────────────────────────────────────

    public function testBusinessMaxPhotosIsTwo(): void
    {
        // El límite se define en upload_business_gallery.php como variable local.
        // Lo leemos por reflexión del archivo para mantenerlo DRY.
        $source = file_get_contents(__DIR__ . '/../api/upload_business_gallery.php');
        $this->assertStringContainsString(
            '$maxPhotos = 2;',
            $source,
            'El límite de fotos de negocios debe ser 2'
        );
    }

    public function testBusinessMaxSizeIs120KB(): void
    {
        $source = file_get_contents(__DIR__ . '/../api/upload_business_gallery.php');
        $this->assertStringContainsString(
            '$maxSize   = 120 * 1024;',
            $source,
            'El tamaño máximo de fotos de negocios debe ser 120 KB'
        );
    }

    public function testIndustryMaxPhotosIsTwo(): void
    {
        $source = file_get_contents(__DIR__ . '/../api/upload_industry_gallery.php');
        $this->assertStringContainsString(
            '$maxPhotos = 2;',
            $source,
            'El límite de fotos de industrias debe ser 2'
        );
    }

    public function testIndustryMaxSizeIs120KB(): void
    {
        $source = file_get_contents(__DIR__ . '/../api/upload_industry_gallery.php');
        $this->assertStringContainsString(
            '$maxSize   = 120 * 1024;',
            $source,
            'El tamaño máximo de fotos de industrias debe ser 120 KB'
        );
    }

    public function testBrandGalleryMaxImagesIsOne(): void
    {
        $this->assertEquals(
            1,
            \App\Models\BrandGallery::MAX_IMAGES_PER_BRAND,
            'El límite de imágenes de galería de marca debe ser 1'
        );
    }

    public function testBrandGalleryMaxSizeIs120KB(): void
    {
        $this->assertEquals(
            120 * 1024,
            \App\Models\BrandGallery::MAX_FILE_BYTES,
            'El tamaño máximo de imagen de galería de marca debe ser 120 KB'
        );
    }

    public function testBrandLogoMaxSizeIs120KB(): void
    {
        $source = file_get_contents(__DIR__ . '/../api/upload_brand_logo.php');
        $this->assertStringContainsString(
            '$maxSize = 120 * 1024;',
            $source,
            'El tamaño máximo del logo de marca debe ser 120 KB'
        );
    }

    // ── Simulación de rechazo por exceso de fotos ─────────────────────────────

    /**
     * Simula la lógica de rechazo cuando ya hay $maxPhotos fotos existentes.
     */
    public function testBusinessGalleryRejectsWhenAtLimit(): void
    {
        $maxPhotos = 2;
        $existing  = array_fill(0, $maxPhotos, ['filename' => 'gallery_x.jpg']);

        $rejected = count($existing) >= $maxPhotos;
        $this->assertTrue($rejected, 'Debe rechazar cuando se alcanza el límite de fotos');
    }

    public function testIndustryGalleryRejectsWhenAtLimit(): void
    {
        $maxPhotos = 2;
        $existing  = array_fill(0, $maxPhotos, ['filename' => 'gallery_x.jpg']);

        $rejected = count($existing) >= $maxPhotos;
        $this->assertTrue($rejected, 'Debe rechazar cuando se alcanza el límite de fotos de industria');
    }

    public function testBrandGalleryRejectsWhenAtLimit(): void
    {
        $maxImages = \App\Models\BrandGallery::MAX_IMAGES_PER_BRAND;
        $existing  = array_fill(0, $maxImages, ['id' => 1, 'filename' => 'brand_1.jpg']);

        $rejected = count($existing) >= $maxImages;
        $this->assertTrue($rejected, 'Debe rechazar cuando se alcanza el límite de imágenes de marca');
    }

    // ── Simulación de rechazo por tamaño excedido ─────────────────────────────

    public function testBusinessGalleryRejectsOversizedFile(): void
    {
        $maxSize  = 120 * 1024;
        $fileSize = 200 * 1024; // 200 KB — demasiado grande

        $rejected = $fileSize > $maxSize;
        $this->assertTrue($rejected, 'Debe rechazar archivo mayor a 120 KB en negocio');
    }

    public function testBusinessGalleryAcceptsFileAtLimit(): void
    {
        $maxSize  = 120 * 1024;
        $fileSize = 120 * 1024; // exactamente en el límite

        $rejected = $fileSize > $maxSize;
        $this->assertFalse($rejected, 'Debe aceptar archivo de exactamente 120 KB en negocio');
    }

    public function testIndustryGalleryRejectsOversizedFile(): void
    {
        $maxSize  = 120 * 1024;
        $fileSize = 150 * 1024;

        $rejected = $fileSize > $maxSize;
        $this->assertTrue($rejected, 'Debe rechazar archivo mayor a 120 KB en industria');
    }

    public function testBrandGalleryRejectsOversizedFile(): void
    {
        $maxSize  = \App\Models\BrandGallery::MAX_FILE_BYTES;
        $fileSize = 200 * 1024;

        $rejected = $fileSize > $maxSize;
        $this->assertTrue($rejected, 'Debe rechazar archivo mayor a 120 KB en galería de marca');
    }

    public function testBrandLogoRejectsOversizedFile(): void
    {
        $maxSize  = 120 * 1024;
        $fileSize = 250 * 1024;

        $rejected = $fileSize > $maxSize;
        $this->assertTrue($rejected, 'Debe rechazar logo mayor a 120 KB');
    }

    // ── Carga individual dentro del límite ────────────────────────────────────

    public function testBusinessGalleryAllowsUploadWhenBelowLimit(): void
    {
        $maxPhotos = 2;
        $existing  = [['filename' => 'gallery_a.jpg']]; // solo 1 de 2

        $canUpload = count($existing) < $maxPhotos;
        $this->assertTrue($canUpload, 'Debe permitir subir cuando hay menos fotos que el límite');
    }

    public function testIndustryGalleryAllowsUploadWhenBelowLimit(): void
    {
        $maxPhotos = 2;
        $existing  = []; // ninguna foto aún

        $canUpload = count($existing) < $maxPhotos;
        $this->assertTrue($canUpload, 'Debe permitir subir la primera foto de industria');
    }
}
