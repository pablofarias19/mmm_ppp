<?php
/**
 * Tests for encuesta render_mode logic.
 *
 * Validates that compute_encuesta_render_mode() correctly distinguishes:
 *   - 'link'   : SIMPLE survey with external link (Google Forms, etc.)
 *   - 'pro'    : PRO survey with native questions
 *   - 'simple' : basic survey with no link and no questions
 *
 * Tests correspond to the acceptance criteria in the PR:
 *   Case A — PRO encuesta with preguntas  → render_mode = 'pro'
 *   Case B — SIMPLE encuesta with link    → render_mode = 'link'
 *   Case C — Same title, both types       → correctly differentiated by render_mode
 */

use PHPUnit\Framework\TestCase;

// Load the helper function from the API file safely.
// We stub the functions that would cause side-effects (output, exit, session).
if (!function_exists('compute_encuesta_render_mode')) {
    if (!function_exists('respond_success')) {
        function respond_success($data, $msg = 'OK') { /* stub */ }
    }
    if (!function_exists('respond_error')) {
        function respond_error($msg, $code = 400) { /* stub */ }
    }
    if (!function_exists('parse_aggregate_ids')) {
        function parse_aggregate_ids($r) { return []; }
    }
    if (!function_exists('isAdmin')) {
        function isAdmin() { return false; }
    }
    // Prevent the Database autoload from failing by defining a stub namespace
    // before requiring the file.
    // We only need the pure helper function — isolate it by requiring conditionally.
    require_once __DIR__ . '/../api/encuestas_render_helper.php';
}

class EncuestaRenderModeTest extends TestCase
{
    // ── Case A: PRO survey ────────────────────────────────────────────────────

    public function testProSurveyWithQuestionsReturnsProMode(): void
    {
        $preguntas = [
            ['id' => 1, 'texto_pregunta' => '¿Qué tan satisfecho estás?', 'tipo' => 'escala'],
            ['id' => 2, 'texto_pregunta' => '¿Recomendarías el servicio?', 'tipo' => 'si_no'],
        ];
        $mode = compute_encuesta_render_mode(null, $preguntas);
        $this->assertSame('pro', $mode, 'A PRO survey (with preguntas, no link) must return render_mode=pro');
    }

    public function testProSurveyWithEmptyLinkReturnsProMode(): void
    {
        $preguntas = [['id' => 1, 'texto_pregunta' => '¿Opinión?', 'tipo' => 'texto_libre']];
        $mode = compute_encuesta_render_mode('', $preguntas);
        $this->assertSame('pro', $mode, 'Empty string link with preguntas should still return pro');
    }

    // ── Case B: SIMPLE survey ─────────────────────────────────────────────────

    public function testSimpleSurveyWithLinkReturnsLinkMode(): void
    {
        $mode = compute_encuesta_render_mode('https://forms.google.com/abc', []);
        $this->assertSame('link', $mode, 'A SIMPLE survey (with external link) must return render_mode=link');
    }

    public function testSimpleSurveyWithLinkTakesPrecedenceOverPreguntas(): void
    {
        // Edge case: a record with both link and preguntas — link wins (SIMPLE takes precedence)
        $preguntas = [['id' => 1, 'texto_pregunta' => 'Some question', 'tipo' => 'si_no']];
        $mode = compute_encuesta_render_mode('https://typeform.com/xyz', $preguntas);
        $this->assertSame('link', $mode, 'When both link and preguntas exist, link (SIMPLE) must take precedence');
    }

    // ── Case C: Same title, both types ───────────────────────────────────────

    public function testTwoSurveysWithSameTitleAreDifferentiated(): void
    {
        // Simulates two records with the same title but different data:
        // one PRO (questions), one SIMPLE (link)
        $proMode  = compute_encuesta_render_mode(null, [['id' => 1, 'texto_pregunta' => 'Pregunta']]);
        $linkMode = compute_encuesta_render_mode('https://forms.google.com/same', []);

        $this->assertSame('pro',  $proMode,  'PRO survey must be identified as pro');
        $this->assertSame('link', $linkMode, 'SIMPLE survey must be identified as link');
        $this->assertNotSame($proMode, $linkMode, 'Same-title surveys must have different render_modes');
    }

    // ── Fallback case ─────────────────────────────────────────────────────────

    public function testSurveyWithNoLinkAndNoQuestionsReturnsSimpleMode(): void
    {
        $mode = compute_encuesta_render_mode(null, []);
        $this->assertSame('simple', $mode, 'Survey with no link and no preguntas must return render_mode=simple');
    }

    public function testSurveyWithNullEverythingReturnsSimpleMode(): void
    {
        $mode = compute_encuesta_render_mode(null, []);
        $this->assertSame('simple', $mode);
    }
}

