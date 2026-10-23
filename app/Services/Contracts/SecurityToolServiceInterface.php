<?php

namespace App\Services\Contracts;

use App\Models\Application;

interface SecurityToolServiceInterface
{
    /**
     * Get the unique slug identifier for the tool.
     */
    public function getSlug(): string;

    /**
     * Get human-readable tool name.
     */
    public function getName(): string;

    /**
     * Check if the tool service is online and healthy.
     */
    public function isOnline(): bool;

    /**
     * Perform detailed health and latency check.
     */
    public function checkHealth(): array;

    /**
     * Execute a security analysis or instrumentation action.
     */
    public function execute(Application $application, string $action = 'default', array $params = []): array;
}
