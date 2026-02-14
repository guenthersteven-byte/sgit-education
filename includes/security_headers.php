<?php
/**
 * ============================================================================
 * sgiT Education - Security Headers
 * ============================================================================
 * 
 * Setzt wichtige HTTP-Security-Header für alle Seiten
 * Include am Anfang jeder PHP-Datei VOR jeglicher Ausgabe
 * 
 * @version 1.0
 * @date 08.12.2025
 * @author sgiT Solution Engineering & IT Services
 * ============================================================================
 */

// Nur setzen wenn noch keine Header gesendet wurden
if (!headers_sent()) {
    // Clickjacking-Schutz
    header('X-Frame-Options: SAMEORIGIN');
    
    // MIME-Type Sniffing verhindern
    header('X-Content-Type-Options: nosniff');
    
    // XSS-Filter aktivieren (Legacy-Browser)
    header('X-XSS-Protection: 1; mode=block');
    
    // Content Security Policy
    // unsafe-inline noetig fuer Inline-JS in PHP-Seiten (Quiz, Spiele)
    // unsafe-eval entfernt (nicht benoetigt)
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob:; font-src 'self'; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'; object-src 'none'");

    // Referrer Policy
    header('Referrer-Policy: strict-origin-when-cross-origin');

    // Permissions Policy
    header('Permissions-Policy: geolocation=(), microphone=(self), camera=(self)');

    // HSTS - Browser soll nur HTTPS nutzen (1 Jahr)
    // Sicher weil NPM Reverse Proxy immer SSL terminiert
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

    // Cross-Origin Isolation
    header('Cross-Origin-Opener-Policy: same-origin');
    header('Cross-Origin-Resource-Policy: same-origin');
}
