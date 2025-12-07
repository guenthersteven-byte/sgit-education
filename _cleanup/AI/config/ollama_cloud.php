<?php
/**
 * sgiT Education - Ollama Modell-Konfiguration
 * 
 * @version 1.2
 * @date 02.12.2025
 * 
 * DEIN SYSTEM: 16 GB RAM, Intel i7-8550U
 * AKTUELL FUNKTIONIERT: llama3.2:latest ✅
 */

return [
    // =========================================================================
    // WICHTIG: MODELLE LAUFEN LOKAL!
    // =========================================================================
    //
    // Dein System: 16 GB RAM, Intel i7-8550U
    // Aktuell läuft: llama3.2:latest (8B) ✅
    //
    // HINWEIS: Nicht alle 8B Modelle brauchen gleich viel RAM!
    //   - llama3.2:latest → ~4.7 GB Download, ~6-7 GB RAM → FUNKTIONIERT ✅
    //   - qwen3:8b → ~5.2 GB Download, ~10.6 GB RAM → ZU VIEL! ❌
    //
    // =========================================================================
    
    // Account Info (optional)
    'api_key' => 'l1d08bfc0e9554102b810b1cc55fb23d6.T-Ew0xIZS96L7XiDkXDQ7d7a',
    'account' => 'guenthersteven',
    'key_name' => 'sgit-edu',
    'device' => 'Win11-SGU',
    
    // =========================================================================
    // MODELL-LISTE
    // =========================================================================
    'cloud_models' => [
        
        // =====================================================================
        // ✅ FUNKTIONIERT AUF DEINEM SYSTEM (16 GB RAM)
        // =====================================================================
        
        'llama3.2:latest' => [
            'name' => 'Llama 3.2 8B ⭐ AKTUELL',
            'size' => '8B',
            'ram' => '~7 GB',
            'download' => '~4.7 GB',
            'recommended' => true,
            'note' => 'Meta, läuft stabil!'
        ],
        
        'qwen3:4b' => [
            'name' => 'Qwen3 4B 🐛 BUG',
            'size' => '4B',
            'ram' => '~38.9 GB (!)',  // BUG: Sollte nur ~5 GB sein!
            'download' => '~2.5 GB',
            'recommended' => false,  // BUG-006: HTTP 500!
            'note' => '🐛 BUG: Meldet 38.9 GB RAM-Bedarf! Ollama-Bug oder korrupter Download.'
        ],
        
        'phi3:mini' => [
            'name' => 'Phi-3 Mini',
            'size' => '3.8B',
            'ram' => '~4 GB',
            'download' => '~2.3 GB',
            'recommended' => true,
            'note' => 'Microsoft, schnell'
        ],
        
        'gemma2:2b' => [
            'name' => 'Gemma 2 2B',
            'size' => '2B',
            'ram' => '~3 GB',
            'download' => '~1.6 GB',
            'recommended' => true,
            'note' => 'Google, klein'
        ],
        
        'tinyllama:latest' => [
            'name' => 'TinyLlama 1.1B',
            'size' => '1.1B',
            'ram' => '~2 GB',
            'download' => '~600 MB',
            'recommended' => true,
            'note' => 'Fallback, sehr schnell'
        ],
        
        // =====================================================================
        // ❌ FUNKTIONIERT NICHT (braucht mehr RAM als verfügbar)
        // =====================================================================
        
        'qwen3:8b' => [
            'name' => 'Qwen3 8B ❌ (braucht 10.6 GB)',
            'size' => '8B',
            'ram' => '~10.6 GB',
            'download' => '~5.2 GB',
            'recommended' => false,
            'note' => 'FEHLER: Nicht genug RAM!'
        ],
        
        'mistral:7b' => [
            'name' => 'Mistral 7B ⚠️',
            'size' => '7B',
            'ram' => '~8 GB',
            'download' => '~4.1 GB',
            'recommended' => false,
            'note' => 'Möglicherweise zu groß'
        ],
        
        'deepseek-r1:8b' => [
            'name' => 'DeepSeek R1 8B ⚠️',
            'size' => '8B',
            'ram' => '~8-10 GB',
            'download' => '~5 GB',
            'recommended' => false,
            'note' => 'Ungetestet, wahrscheinlich zu groß'
        ],
    ],
    
    // Standard-Modell = das stabil läuft!
    'default_cloud_model' => 'llama3.2:latest',
];
