<?php
/**
 * sgiT Education - Bot Output Helper
 * 
 * Hilft beim Live-Output in Docker/nginx/PHP-FPM Umgebungen
 * 
 * Problem: ob_implicit_flush() funktioniert nicht in PHP-FPM
 * Lösung: Spezielle Header + Padding + Flush
 * 
 * @version 1.0
 * @date 06.12.2025
 */

class BotOutputHelper {
    
    private static $initialized = false;
    
    /**
     * Initialisiert Live-Output für Docker/nginx/PHP-FPM
     * MUSS vor jeder HTML-Ausgabe aufgerufen werden!
     */
    public static function init() {
        if (self::$initialized) return;
        
        // Deaktiviere alle Pufferung
        ini_set('output_buffering', 'off');
        ini_set('zlib.output_compression', false);
        
        // Wichtig für nginx: Deaktiviere Proxy-Buffering
        if (!headers_sent()) {
            header('X-Accel-Buffering: no');  // nginx
            header('Cache-Control: no-cache');
            header('Content-Type: text/html; charset=UTF-8');
        }
        
        // Alle Output-Buffer leeren
        while (ob_get_level()) {
            ob_end_flush();
        }
        
        // Implicit flush aktivieren (für Apache als Fallback)
        ob_implicit_flush(true);
        
        self::$initialized = true;
    }
    
    /**
     * Gibt Text aus und flusht sofort
     * 
     * @param string $text Der auszugebende Text
     * @param bool $newline Zeilenumbruch anhängen
     */
    public static function output($text, $newline = true) {
        if (!self::$initialized) {
            self::init();
        }
        
        echo $text;
        if ($newline) echo "\n";
        
        // Padding für PHP-FPM Buffer (mind. 4KB für ersten Flush)
        static $firstOutput = true;
        if ($firstOutput) {
            echo str_repeat(' ', 4096);
            $firstOutput = false;
        }
        
        // Flush erzwingen
        if (ob_get_level()) {
            ob_flush();
        }
        flush();
    }
    
    /**
     * Gibt eine Log-Zeile mit Timestamp aus
     */
    public static function log($message, $type = 'info') {
        $icons = [
            'info' => 'ℹ️',
            'success' => '✅',
            'warning' => '⚠️',
            'error' => '❌',
            'progress' => '🔄'
        ];
        
        $icon = $icons[$type] ?? '•';
        $time = date('H:i:s');
        
        self::output("[$time] $icon $message");
    }
    
    /**
     * Gibt einen Fortschrittsbalken aus (für Terminals/CLI)
     */
    public static function progress($current, $total, $label = '') {
        $percent = round(($current / $total) * 100);
        $bar = str_repeat('█', $percent / 5) . str_repeat('░', 20 - ($percent / 5));
        
        self::output("[$bar] $percent% $label", true);
    }
}
