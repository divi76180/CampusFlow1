<?php
/**
 * CampusFlow - Supabase REST API & PostgreSQL Client
 * Connects CampusFlow seamlessly to Supabase cloud database
 */

declare(strict_types=1);

class SupabaseClient {
    private static ?array $config = null;

    public static function loadConfig(): array {
        if (self::$config !== null) {
            return self::$config;
        }

        $configFile = __DIR__ . '/supabase_env.php';
        if (file_exists($configFile)) {
            self::$config = require $configFile;
        } else {
            self::$config = [
                'enabled' => false,
                'url' => '',
                'key' => '',
                'db_host' => '',
                'db_port' => '5432',
                'db_name' => 'postgres',
                'db_user' => 'postgres',
                'db_pass' => ''
            ];
        }
        return self::$config;
    }

    public static function isEnabled(): bool {
        $cfg = self::loadConfig();
        return !empty($cfg['enabled']) && !empty($cfg['url']) && !empty($cfg['key']);
    }

    public static function getUrl(): string {
        $cfg = self::loadConfig();
        $url = trim($cfg['url'] ?? '');
        $url = preg_replace('#/rest/v1/?$#i', '', $url);
        return rtrim($url, '/');
    }

    public static function getKey(): string {
        $cfg = self::loadConfig();
        return $cfg['key'] ?? '';
    }

    /**
     * Send HTTP Request to Supabase PostgREST API
     * @param string $endpoint (e.g. 'users', 'leave_requests')
     * @param string $method ('GET', 'POST', 'PATCH', 'DELETE')
     * @param array|null $params
     * @param array $headers
     * @return array [success => bool, status => int, data => mixed, error => string|null]
     */
    public static function request(string $endpoint, string $method = 'GET', ?array $params = null, array $headers = []): array {
        $url = self::getUrl() . '/rest/v1/' . ltrim($endpoint, '/');
        $key = self::getKey();

        if (empty($url) || empty($key)) {
            return ['success' => false, 'status' => 0, 'data' => null, 'error' => 'Supabase URL or API Key is not configured.'];
        }

        $defaultHeaders = [
            'apikey: ' . $key,
            'Authorization: Bearer ' . $key,
            'Content-Type: application/json',
            'Prefer: return=representation'
        ];

        $allHeaders = array_merge($defaultHeaders, $headers);

        $ch = curl_init();

        if ($method === 'GET' && !empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $allHeaders);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 6);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        } elseif ($method === 'PATCH' || $method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return ['success' => false, 'status' => $httpCode, 'data' => null, 'error' => 'cURL Error: ' . $curlError];
        }

        $decoded = json_decode((string)$response, true);

        if ($httpCode >= 200 && $httpCode < 300) {
            return ['success' => true, 'status' => $httpCode, 'data' => $decoded, 'error' => null];
        }

        $errMsg = is_array($decoded) && isset($decoded['message']) ? $decoded['message'] : "HTTP {$httpCode}: {$response}";
        return ['success' => false, 'status' => $httpCode, 'data' => $decoded, 'error' => $errMsg];
    }

    /**
     * Test Connection to Supabase
     */
    public static function testConnection(): array {
        return self::request('users?select=id,username,role&limit=1', 'GET');
    }
}
