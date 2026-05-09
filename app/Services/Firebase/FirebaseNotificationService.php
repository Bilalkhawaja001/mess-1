<?php

namespace App\Services\Firebase;

class FirebaseNotificationService
{
    public function getAccessToken(): ?string
    {
        $path = env('FIREBASE_CREDENTIALS');

        if (! $path || ! file_exists($path)) {
            return null;
        }

        $json = json_decode(file_get_contents($path), true);

        if (
            empty($json['project_id']) ||
            empty($json['client_email']) ||
            empty($json['private_key'])
        ) {
            return null;
        }

        $now = time();

        $header = ['alg' => 'RS256', 'typ' => 'JWT'];

        $claim = [
            'iss' => $json['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ];

        $jwtUnsigned = $this->base64Url(json_encode($header)) . '.' . $this->base64Url(json_encode($claim));

        $signed = openssl_sign(
            $jwtUnsigned,
            $signature,
            $json['private_key'],
            'sha256WithRSAEncryption'
        );

        if (! $signed) {
            return null;
        }

        $jwt = $jwtUnsigned . '.' . $this->base64Url($signature);

        $ch = curl_init('https://oauth2.googleapis.com/token');

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]),
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_TIMEOUT => 20,
        ]);

        $response = curl_exec($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode((string) $response, true);

        if ($http !== 200 || empty($data['access_token'])) {
            return null;
        }

        return $data['access_token'];
    }

    public function sendToToken(string $deviceToken, string $title, string $body, array $data = []): array
    {
        $path = env('FIREBASE_CREDENTIALS');

        if (! $path || ! file_exists($path)) {
            return ['ok' => false, 'error' => 'Firebase credentials file missing.'];
        }

        $json = json_decode(file_get_contents($path), true);
        $projectId = $json['project_id'] ?? null;
        $accessToken = $this->getAccessToken();

        if (! $projectId || ! $accessToken) {
            return ['ok' => false, 'error' => 'Firebase access token failed.'];
        }

        $payload = [
            'message' => [
                'token' => $deviceToken,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => array_map('strval', $data),
            ],
        ];

        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT => 20,
        ]);

        $response = curl_exec($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        return [
            'ok' => $http >= 200 && $http < 300,
            'http' => $http,
            'error' => $error ?: null,
            'response' => json_decode((string) $response, true) ?: $response,
        ];
    }

    private function base64Url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
