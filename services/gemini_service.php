<?php
class GeminiService {
    private $api_key;
    
    public function __construct($api_key) {
        $this->api_key = $api_key;
    }
    
    public function identifyFlower($image_path) {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=" . $this->api_key;
        
        // Read image file and convert to base64
        $image_data = file_get_contents($image_path);
        $base64_image = base64_encode($image_data);
        
        $payload = [
            'contents' => [
                [
                    'parts' => [
                        [
                            'text' => "Identify this flower and provide: 1. Scientific name, 2. Common name, 3. Brief description (50 words). Format as JSON: {\"scientific_name\": \"...\", \"common_name\": \"...\", \"description\": \"...\"}"
                        ],
                        [
                            'inline_data' => [
                                'mime_type' => mime_content_type($image_path),
                                'data' => $base64_image
                            ]
                        ]
                    ]
                ]
            ]
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return $this->parseResponse($response);
    }
    
    private function parseResponse($response) {
        $data = json_decode($response, true);
        
        if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            return null;
        }
        
        $text = $data['candidates'][0]['content']['parts'][0]['text'];
        
        // Extract JSON from the response
        if (preg_match('/\{.*\}/s', $text, $matches)) {
            return json_decode($matches[0], true);
        }
        
        return null;
    }
}
?>