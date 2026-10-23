<?php

namespace App\Services;

use App\Models\Application;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiService
{
    protected ?string $baseUrl;
    protected ?string $apiKey;
    protected string $provider;

    public function __construct()
    {
        $this->baseUrl = config('services.ai.url', env('AI_SERVICE_URL'));
        $this->apiKey = config('services.ai.key', env('AI_API_KEY'));
        $this->provider = config('services.ai.provider', env('AI_PROVIDER', 'internal'));
    }

    public function isOnline(): bool
    {
        if (empty($this->baseUrl) && empty($this->apiKey)) {
            return false;
        }
        $health = $this->checkHealth();
        return $health['online'] ?? false;
    }

    public function checkHealth(): array
    {
        if (empty($this->baseUrl) && empty($this->apiKey)) {
            return [
                'online' => false,
                'status' => 'not_configured',
                'latency_ms' => null,
                'service_url' => null,
                'message' => 'AI Service is not configured. (Future Phase Integration)',
            ];
        }

        try {
            if ($this->baseUrl) {
                $startTime = microtime(true);
                $response = Http::timeout(3)->get("{$this->baseUrl}/health");
                $latency = round((microtime(true) - $startTime) * 1000);
                return [
                    'online' => $response->successful(),
                    'status' => $response->successful() ? 'online' : 'offline',
                    'latency_ms' => $latency,
                    'service_url' => $this->baseUrl,
                ];
            }

            return [
                'online' => true,
                'status' => 'configured_remote_llm',
                'latency_ms' => null,
                'service_url' => 'cloud-provider',
            ];
        } catch (\Exception $e) {
            return [
                'online' => false,
                'status' => 'unreachable',
                'latency_ms' => null,
                'service_url' => $this->baseUrl,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Analyze normalized unified findings using AI to provide executive summaries and remediation plans.
     */
    public function analyzeUnifiedFindings(array $unifiedFindingsPayload): array
    {
        if (!$this->isOnline()) {
            return [
                'status' => 'offline',
                'message' => 'AI Service is not yet configured.',
                'summary' => 'AI reasoning module is ready for integration in the next platform phase.',
                'remediations' => [],
            ];
        }

        // Future Phase: call remote or containerized LLM with structured findings
        return [
            'status' => 'success',
            'summary' => 'AI analysis placeholder.',
            'findings_processed' => count($unifiedFindingsPayload['findings'] ?? []),
        ];
    }
}
