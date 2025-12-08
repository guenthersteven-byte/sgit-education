<?php
/**
 * ============================================================================
 * sgiT Education - Rate Limiter
 * ============================================================================
 * 
 * Einfaches Session-basiertes Rate-Limiting zum Schutz vor Brute-Force
 * 
 * @version 1.0
 * @date 08.12.2025
 * @author sgiT Solution Engineering & IT Services
 * ============================================================================
 */

class RateLimiter {
    
    /**
     * Prüft ob ein Request erlaubt ist
     * 
     * @param string $key Eindeutiger Key (z.B. 'login', 'quiz_api')
     * @param int $maxRequests Maximale Requests pro Zeitfenster
     * @param int $windowSeconds Zeitfenster in Sekunden
     * @return array ['allowed' => bool, 'remaining' => int, 'reset_in' => int]
     */
    public static function check($key, $maxRequests = 30, $windowSeconds = 60) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $now = time();
        $sessionKey = 'rate_limit_' . $key;
        
        // Initialisieren falls nicht vorhanden
        if (!isset($_SESSION[$sessionKey])) {
            $_SESSION[$sessionKey] = [
                'count' => 0,
                'window_start' => $now
            ];
        }
        
        $data = &$_SESSION[$sessionKey];
        
        // Zeitfenster abgelaufen? Reset!
        if ($now - $data['window_start'] >= $windowSeconds) {
            $data['count'] = 0;
            $data['window_start'] = $now;
        }
        
        // Request zählen
        $data['count']++;
        
        // Verbleibende Requests berechnen
        $remaining = max(0, $maxRequests - $data['count']);
        $resetIn = $windowSeconds - ($now - $data['window_start']);
        
        // Prüfen ob erlaubt
        $allowed = $data['count'] <= $maxRequests;
        
        return [
            'allowed' => $allowed,
            'remaining' => $remaining,
            'reset_in' => $resetIn,
            'count' => $data['count'],
            'limit' => $maxRequests
        ];
    }
    
    /**
     * Blockiert Request falls Rate-Limit überschritten
     * Gibt HTTP 429 zurück
     */
    public static function enforce($key, $maxRequests = 30, $windowSeconds = 60) {
        $result = self::check($key, $maxRequests, $windowSeconds);
        
        if (!$result['allowed']) {
            http_response_code(429);
            header('Retry-After: ' . $result['reset_in']);
            header('X-RateLimit-Limit: ' . $result['limit']);
            header('X-RateLimit-Remaining: 0');
            header('X-RateLimit-Reset: ' . (time() + $result['reset_in']));
            
            if (self::isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode([
                    'error' => 'Rate limit exceeded',
                    'retry_after' => $result['reset_in'],
                    'message' => "Zu viele Anfragen. Bitte warte {$result['reset_in']} Sekunden."
                ]);
            } else {
                echo "429 Too Many Requests - Bitte warte {$result['reset_in']} Sekunden.";
            }
            exit;
        }
        
        // Rate-Limit Headers setzen (für erlaubte Requests)
        header('X-RateLimit-Limit: ' . $result['limit']);
        header('X-RateLimit-Remaining: ' . $result['remaining']);
        
        return $result;
    }
    
    /**
     * Reset für einen Key
     */
    public static function reset($key) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        unset($_SESSION['rate_limit_' . $key]);
    }
    
    /**
     * Prüft ob AJAX Request
     */
    private static function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
