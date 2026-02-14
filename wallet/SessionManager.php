<?php
/**
 * ============================================================================
 * sgiT Education - Session Manager
 * ============================================================================
 * 
 * Verwaltet User-Sessions für die Lernplattform.
 * Speichert welches Kind eingeloggt ist und stellt Session-Funktionen bereit.
 * 
 * @author sgiT Solution Engineering & IT Services
 * @version 1.0
 * @date 02.12.2025
 * ============================================================================
 */

// Session starten falls noch nicht geschehen
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class SessionManager {
    
    /** @var string Session-Key für Kind-ID */
    private const KEY_CHILD_ID = 'sgit_child_id';
    
    /** @var string Session-Key für Kind-Name */
    private const KEY_CHILD_NAME = 'sgit_child_name';
    
    /** @var string Session-Key für Kind-Avatar */
    private const KEY_CHILD_AVATAR = 'sgit_child_avatar';
    
    /** @var string Session-Key für Kind-Alter */
    private const KEY_CHILD_AGE = 'sgit_child_age';
    
    /** @var string Session-Key für Login-Zeit */
    private const KEY_LOGIN_TIME = 'sgit_login_time';
    
    // ========================================================================
    // LOGIN / LOGOUT
    // ========================================================================
    
    /**
     * Loggt ein Kind ein
     * 
     * @param array $child Kind-Daten aus der Datenbank
     * @return bool
     */
    public static function login(array $child): bool {
        if (empty($child['id'])) {
            return false;
        }

        // Session-ID regenerieren (Session-Fixation-Schutz)
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }

        $_SESSION[self::KEY_CHILD_ID] = (int) $child['id'];
        $_SESSION[self::KEY_CHILD_NAME] = $child['child_name'] ?? 'Unbekannt';
        $_SESSION[self::KEY_CHILD_AVATAR] = $child['avatar'] ?? '👧';
        $_SESSION[self::KEY_CHILD_AGE] = (int) ($child['age'] ?? 10);
        $_SESSION[self::KEY_LOGIN_TIME] = time();
        
        return true;
    }
    
    /**
     * Loggt das aktuelle Kind aus
     */
    public static function logout(): void {
        unset($_SESSION[self::KEY_CHILD_ID]);
        unset($_SESSION[self::KEY_CHILD_NAME]);
        unset($_SESSION[self::KEY_CHILD_AVATAR]);
        unset($_SESSION[self::KEY_CHILD_AGE]);
        unset($_SESSION[self::KEY_LOGIN_TIME]);
    }
    
    /**
     * Prüft ob ein Kind eingeloggt ist
     * 
     * @return bool
     */
    public static function isLoggedIn(): bool {
        return !empty($_SESSION[self::KEY_CHILD_ID]);
    }
    
    /**
     * Erzwingt Login - Redirect wenn nicht eingeloggt
     * 
     * @param string $redirectUrl URL zur Login-Seite
     */
    public static function requireLogin(string $redirectUrl = '/Education/wallet/login.php'): void {
        if (!self::isLoggedIn()) {
            header('Location: ' . $redirectUrl);
            exit;
        }
    }
    
    // ========================================================================
    // GETTER
    // ========================================================================
    
    /**
     * Holt die ID des eingeloggten Kindes
     * 
     * @return int|null
     */
    public static function getChildId(): ?int {
        return $_SESSION[self::KEY_CHILD_ID] ?? null;
    }
    
    /**
     * Holt den Namen des eingeloggten Kindes
     * 
     * @return string|null
     */
    public static function getChildName(): ?string {
        return $_SESSION[self::KEY_CHILD_NAME] ?? null;
    }
    
    /**
     * Holt den Avatar des eingeloggten Kindes
     * 
     * @return string|null
     */
    public static function getChildAvatar(): ?string {
        return $_SESSION[self::KEY_CHILD_AVATAR] ?? null;
    }
    
    /**
     * Holt das Alter des eingeloggten Kindes
     * 
     * @return int|null
     */
    public static function getChildAge(): ?int {
        return $_SESSION[self::KEY_CHILD_AGE] ?? null;
    }
    
    /**
     * Holt alle Session-Daten des Kindes
     * 
     * @return array|null
     */
    public static function getChild(): ?array {
        if (!self::isLoggedIn()) {
            return null;
        }
        
        return [
            'id' => self::getChildId(),
            'name' => self::getChildName(),
            'avatar' => self::getChildAvatar(),
            'age' => self::getChildAge(),
            'login_time' => $_SESSION[self::KEY_LOGIN_TIME] ?? null
        ];
    }
    
    // ========================================================================
    // SCHWIERIGKEIT BASIEREND AUF ALTER
    // ========================================================================
    
    /**
     * Ermittelt die Schwierigkeitsstufe basierend auf dem Alter
     * 
     * @return int 1-3 (Leicht, Mittel, Schwer)
     */
    public static function getDifficulty(): int {
        $age = self::getChildAge();
        
        if ($age === null) {
            return 1; // Default: Leicht
        }
        
        if ($age <= 7) {
            return 1; // Leicht (5-7 Jahre)
        } elseif ($age <= 11) {
            return 2; // Mittel (8-11 Jahre)
        } else {
            return 3; // Schwer (12-15 Jahre)
        }
    }
    
    /**
     * Holt den Schwierigkeitsgrad als Text
     * 
     * @return string
     */
    public static function getDifficultyText(): string {
        $difficulty = self::getDifficulty();
        
        switch ($difficulty) {
            case 1: return 'Leicht';
            case 2: return 'Mittel';
            case 3: return 'Schwer';
            default: return 'Unbekannt';
        }
    }
    
    /**
     * Holt die Altersgruppe als Text
     * 
     * @return string
     */
    public static function getAgeGroup(): string {
        $age = self::getChildAge();
        
        if ($age === null) {
            return 'Unbekannt';
        }
        
        if ($age <= 7) {
            return '5-7 Jahre';
        } elseif ($age <= 11) {
            return '8-11 Jahre';
        } else {
            return '12-15 Jahre';
        }
    }
    
    /**
     * Holt vollständige Kind-Daten (Alias für getChild)
     * Wrapper für Instanz-Aufrufe
     * 
     * @return array|null
     */
    public function getChildData(): ?array {
        return self::getChild();
    }
}
