<?php
/**
 * Translation Helper Functions
 * Provides English-to-Thai label translation with hybrid database caching.
 */

if (!function_exists('translate_word_via_api')) {
    /**
     * Translate an English word to Thai via MyMemory Translation API with fallback/timeout checks.
     * 
     * @param string $word English word to translate
     * @return string|null Translated Thai word or null if translation failed
     */
    function translate_word_via_api($word) {
        $word = trim($word);
        if (empty($word)) return null;

        $url = 'https://api.mymemory.translated.net/get?q=' . urlencode($word) . '&langpair=en|th';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3); // Max 3 seconds to avoid UI freezing
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For compatibility with local XAMPP SSL configurations
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code === 200 && !empty($response)) {
            $result = json_decode($response, true);
            if (isset($result['responseData']['translatedText'])) {
                $translated = trim($result['responseData']['translatedText']);
                
                // Discard translations that are too long (e.g. definitions) or contain brackets/punctuation
                if (mb_strlen($translated, 'UTF-8') > 30 || preg_match('/[।\.!?(){}\[\]]|;|:/u', $translated)) {
                    return null;
                }

                // Ensure the translation is actually different and not empty
                if (!empty($translated) && strtolower($translated) !== strtolower($word)) {
                    return $translated;
                }
            }
        }

        return null;
    }
}

if (!function_exists('translate_word')) {
    /**
     * Translate an English word using DB cache first, falling back to API and caching the result.
     * 
     * @param PDO $pdo PDO connection instance
     * @param string $word English word
     * @return string|null Translated Thai word or null if not found
     */
    function translate_word($pdo, $word) {
        $word = mb_strtolower(trim($word));
        if (empty($word)) return null;

        try {
            // 1. Primary Cache Check (Database query)
            $sql = "SELECT thai_word FROM translation_cache WHERE english_word = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$word]);
            $cached = $stmt->fetchColumn();

            if ($cached !== false) {
                return $cached;
            }

            // 2. Dynamic Fallback (API Translation)
            $translated = translate_word_via_api($word);

            if (!empty($translated)) {
                // 3. Auto-Learning (Save translation cache back to DB)
                $insert_sql = "INSERT IGNORE INTO translation_cache (english_word, thai_word) VALUES (?, ?)";
                $insert_stmt = $pdo->prepare($insert_sql);
                $insert_stmt->execute([$word, $translated]);
                return $translated;
            }
        } catch (PDOException $e) {
            error_log("Translation DB Error: " . $e->getMessage());
        }

        return null;
    }
}

if (!function_exists('translate_labels')) {
    /**
     * Translate an array of English labels into a combined list of English and Thai labels.
     * 
     * @param PDO $pdo PDO connection instance
     * @param array $labels Array of English label strings
     * @return array Array containing unique English and translated Thai labels
     */
    function translate_labels($pdo, $labels) {
        if (empty($labels) || !is_array($labels)) return [];

        $results = [];
        foreach ($labels as $label) {
            $label = trim($label);
            if (empty($label)) continue;

            $results[] = $label; // Always keep the original English label
            
            $translated = translate_word($pdo, $label);
            if (!empty($translated) && strtolower($translated) !== strtolower($label)) {
                $results[] = $translated; // Add translated Thai label
            }
        }

        return array_values(array_unique($results));
    }
}
?>
