<?php
/**
 * Encuestas render-mode helper — pure functions, no side-effects.
 *
 * Extracted here so unit tests can load them without pulling in the full
 * API endpoint (which starts sessions, connects to DB, etc.).
 */

if (!function_exists('compute_encuesta_render_mode')) {
    /**
     * Determines the render mode for an encuesta.
     *
     * Priority:
     *   1. 'link'   — has a non-empty external link (SIMPLE / Google Forms style)
     *   2. 'pro'    — has native questions in preguntas_encuesta (PRO)
     *   3. 'simple' — no link, no questions (basic info marker)
     *
     * @param string|null $link      The encuesta's link field value
     * @param array       $preguntas Array of preguntas associated with the encuesta
     * @return string 'link' | 'pro' | 'simple'
     */
    function compute_encuesta_render_mode($link, array $preguntas): string {
        if (!empty($link)) return 'link';
        if (!empty($preguntas)) return 'pro';
        return 'simple';
    }
}
