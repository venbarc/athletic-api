<?php

namespace App\Services\Atheletic;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class AtheleticApiClient
{
    private ?string $token = null;

    public function __construct(
        private readonly string $baseUrl,
        private readonly string $username,
        private readonly string $password,
        private readonly int $timeoutSeconds,
        private readonly bool $verifySsl,
    ) {
    }

    public static function fromConfig(): self
    {
        return new self(
            rtrim((string) config('services.atheletic.base_url'), '/'),
            (string) config('services.atheletic.username'),
            (string) config('services.atheletic.password'),
            (int) config('services.atheletic.timeout', 120),
            (bool) config('services.atheletic.verify_ssl', true),
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getDataDumpsList(string $dumpType): array
    {
        $response = $this->authenticatedRequest()
            ->acceptJson()
            ->get('/v1/external_api/get_data_dumps_list', [
                'data_dump_type' => $dumpType,
            ]);

        $this->throwIfFailed($response, "Unable to fetch dumps list for {$dumpType}.");

        $payload = $response->json();

        if (! is_array($payload)) {
            return [];
        }

        if (array_is_list($payload)) {
            return array_values(array_filter($payload, 'is_array'));
        }

        foreach (['data', 'results', 'dumps', 'items'] as $key) {
            $value = $payload[$key] ?? null;

            if (is_array($value)) {
                return array_values(array_filter($value, 'is_array'));
            }
        }

        return [];
    }

    public function downloadDumpFile(int $dumpId, string $targetPath, ?string $fallbackFilePath = null): void
    {
        File::ensureDirectoryExists(dirname($targetPath));
        $initialPath = "{$targetPath}.initial";

        $response = $this->authenticatedRequest()
            ->sink($initialPath)
            ->get('/v1/external_api/get_data_dump_file', [
                'data_dump_id' => $dumpId,
            ]);

        $this->throwIfFailed($response, "Unable to fetch dump file for id {$dumpId}.");

        $contentType = strtolower((string) $response->header('Content-Type', ''));

        if (! str_contains($contentType, 'application/json')) {
            File::move($initialPath, $targetPath);

            return;
        }

        $json = json_decode((string) File::get($initialPath), true);
        File::delete($initialPath);

        if (! is_array($json)) {
            throw new RuntimeException("Dump {$dumpId} returned invalid JSON payload.");
        }

        $filePath = $this->extractFilePath($json) ?? $fallbackFilePath;

        if (! is_string($filePath) || trim($filePath) === '') {
            throw new RuntimeException("Dump {$dumpId} response did not include a downloadable file path.");
        }

        $downloadUrl = $this->resolveFileUrl($filePath);

        $downloadResponse = $this->authenticatedRequest()
            ->sink($targetPath)
            ->get($downloadUrl);

        $this->throwIfFailed($downloadResponse, "Unable to download dump {$dumpId} from {$downloadUrl}.");
    }

    private function authenticatedRequest(): PendingRequest
    {
        return $this->baseRequest()->withToken($this->accessToken());
    }

    private function accessToken(): string
    {
        if ($this->token !== null) {
            return $this->token;
        }

        if ($this->username === '' || $this->password === '') {
            throw new RuntimeException('ATHELETIC_API_USERNAME and ATHELETIC_API_PASSWORD must be configured.');
        }

        $response = $this->baseRequest()
            ->acceptJson()
            ->post('/v1/login/login', [
                'username' => $this->username,
                'password' => $this->password,
            ]);

        $this->throwIfFailed($response, 'Unable to authenticate with Atheletic API.');

        $payload = $response->json();

        if (! is_array($payload)) {
            throw new RuntimeException('Authentication response is not a JSON object.');
        }

        $token = $payload['token']
            ?? $payload['access_token']
            ?? $payload['data']['token']
            ?? $payload['data']['access_token']
            ?? null;

        if (! is_string($token) || trim($token) === '') {
            throw new RuntimeException('Authentication succeeded but token was missing in response.');
        }

        $this->token = trim($token);

        return $this->token;
    }

    private function baseRequest(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->timeout($this->timeoutSeconds)
            ->withOptions(['verify' => $this->verifySsl])
            ->retry(3, 1000, throw: false);
    }

    private function resolveFileUrl(string $filePath): string
    {
        if (Str::startsWith($filePath, ['http://', 'https://'])) {
            return $filePath;
        }

        return rtrim($this->baseUrl, '/').'/'.ltrim($filePath, '/');
    }

    private function extractFilePath(array $payload): ?string
    {
        $candidates = [
            $payload['file_path'] ?? null,
            $payload['path'] ?? null,
            $payload['url'] ?? null,
            $payload['download_url'] ?? null,
            $payload['data']['file_path'] ?? null,
            $payload['data']['path'] ?? null,
            $payload['data']['url'] ?? null,
            $payload['result']['file_path'] ?? null,
            $payload['result']['url'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }
        }

        if (array_is_list($payload) && isset($payload[0]) && is_array($payload[0])) {
            return $this->extractFilePath($payload[0]);
        }

        return null;
    }

    private function throwIfFailed(Response $response, string $message): void
    {
        if ($response->successful()) {
            return;
        }

        $body = trim((string) $response->body());
        $snippet = Str::limit($body, 1000, '');

        throw new RuntimeException(
            "{$message} HTTP {$response->status()}".($snippet !== '' ? ": {$snippet}" : '.')
        );
    }
}
