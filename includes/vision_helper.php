<?php
/**
 * Google Cloud Vision API Helper
 * Provides functions to perform image analysis using Google's Cloud Vision REST API.
 */

require_once __DIR__ . '/../config/vision.php';

if (!function_exists('detect_labels')) {
    /**
     * Send an image to Google Cloud Vision API and detect labels
     * 
     * @param string $image_path Path to the image file (absolute or relative to root)
     * @return array Array of detected labels (lowercase strings)
     */
    function detect_labels($image_path) {
        // Return empty if API key is not configured or is still placeholder
        if (!defined('GOOGLE_VISION_API_KEY') || empty(GOOGLE_VISION_API_KEY) || GOOGLE_VISION_API_KEY === 'YOUR_GOOGLE_CLOUD_VISION_API_KEY') {
            return [];
        }

        // Check if image file exists
        if (!file_exists($image_path)) {
            // If relative path, try to resolve it from the application root
            $resolved_path = __DIR__ . '/../' . $image_path;
            if (file_exists($resolved_path)) {
                $image_path = $resolved_path;
            } else {
                error_log("Google Vision Error: File not found - " . $image_path);
                return [];
            }
        }

        $image_data = @file_get_contents($image_path);
        if ($image_data === false) {
            error_log("Google Vision Error: Failed to read file - " . $image_path);
            return [];
        }

        $base64_image = base64_encode($image_data);

        $payload = json_encode([
            'requests' => [
                [
                    'image' => [
                        'content' => $base64_image
                    ],
                    'features' => [
                        [
                            'type' => 'LABEL_DETECTION',
                            'maxResults' => 15
                        ]
                    ]
                ]
            ]
        ]);

        $url = 'https://vision.googleapis.com/v1/images:annotate?key=' . GOOGLE_VISION_API_KEY;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($payload)
        ]);

        // Disable SSL verification for XAMPP on Windows compatibility
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
            error_log("Google Vision Curl Error: " . $error_msg);
            curl_close($ch);
            return [];
        }

        curl_close($ch);

        if ($http_code !== 200) {
            error_log("Google Vision API Error (HTTP $http_code): " . $response);
            return [];
        }

        $result = json_decode($response, true);
        $labels = [];

        if (!empty($result['responses'][0]['labelAnnotations'])) {
            foreach ($result['responses'][0]['labelAnnotations'] as $annotation) {
                // Only keep labels with score >= 0.6 (60% confidence)
                if (isset($annotation['score']) && $annotation['score'] >= 0.60) {
                    $labels[] = strtolower($annotation['description']);
                }
            }
        }

        return $labels;
    }
}
?>
