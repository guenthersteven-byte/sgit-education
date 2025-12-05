<?php
/**
 * ZENTRALE SESSION-VERWALTUNG
 * Verwaltet alle Module-Sessions und verhindert Duplikate
 */

session_start();

class SessionManager {
    
    public static function initModule($module) {
        $key = "module_$module";
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [
                "total_questions_seen" => 0,
                "sessions_completed" => 0,
                "total_score" => 0,
                "question_history" => [],
                "last_10_sessions" => []
            ];
        }
        
        return $_SESSION[$key];
    }
    
    public static function recordSession($module, $score, $questions_asked) {
        $key = "module_$module";
        
        $_SESSION[$key]["sessions_completed"]++;
        $_SESSION[$key]["total_score"] += $score;
        $_SESSION[$key]["total_questions_seen"] += 10;
        
        // Speichere die letzten 10 Sessions
        $_SESSION[$key]["last_10_sessions"][] = [
            "date" => date("Y-m-d H:i:s"),
            "score" => $score,
            "questions" => $questions_asked
        ];
        
        if (count($_SESSION[$key]["last_10_sessions"]) > 10) {
            array_shift($_SESSION[$key]["last_10_sessions"]);
        }
        
        // Update Question History
        $_SESSION[$key]["question_history"] = array_merge(
            $_SESSION[$key]["question_history"],
            $questions_asked
        );
        
        // Behalte nur die letzten 100 Fragen-IDs
        if (count($_SESSION[$key]["question_history"]) > 100) {
            $_SESSION[$key]["question_history"] = array_slice(
                $_SESSION[$key]["question_history"],
                -100
            );
        }
    }
    
    public static function getAvoidList($module) {
        $key = "module_$module";
        
        if (!isset($_SESSION[$key])) {
            return [];
        }
        
        // Gib die letzten 50 Fragen zurück, die vermieden werden sollen
        return array_slice($_SESSION[$key]["question_history"], -50);
    }
    
    public static function getStatistics($module) {
        $key = "module_$module";
        
        if (!isset($_SESSION[$key])) {
            return null;
        }
        
        $data = $_SESSION[$key];
        
        return [
            "total_questions" => $data["total_questions_seen"],
            "sessions" => $data["sessions_completed"],
            "average_score" => $data["sessions_completed"] > 0 
                ? round($data["total_score"] / $data["sessions_completed"], 1)
                : 0,
            "unique_questions_seen" => count(array_unique($data["question_history"])),
            "recent_sessions" => $data["last_10_sessions"]
        ];
    }
    
    public static function resetModule($module) {
        $key = "module_$module";
        unset($_SESSION[$key]);
    }
    
    public static function getAllModuleStats() {
        $stats = [];
        $modules = [
            "mathematik", "lesen", "englisch", "wissenschaft", "erdkunde",
            "chemie", "physik", "kunst", "musik", "computer",
            "bitcoin", "geschichte", "biologie", "steuern"
        ];
        
        foreach ($modules as $module) {
            $stats[$module] = self::getStatistics($module);
        }
        
        return $stats;
    }
}
?>