<?php

namespace App\Services\Analysis;

class FindingNormalizer
{
    /**
     * Normalize a raw finding or a list of findings from any security tool into ERM's unified schema.
     *
     * @param string $source (e.g. 'frida', 'mobsf', 'objection', 'ai')
     * @param mixed $rawFinding
     * @return array
     */
    public function normalize(string $source, $rawFinding): array
    {
        $normalized = [
            'source' => strtolower($source),
            'finding' => 'Security Observation',
            'severity' => 'info',
            'confidence' => 0.70,
            'evidence' => [],
            'metadata' => [],
            'timestamp' => date('c'),
        ];

        if (is_string($rawFinding)) {
            $normalized['finding'] = $rawFinding;
            return $normalized;
        }

        if (is_array($rawFinding)) {
            $normalized['finding'] = $rawFinding['finding'] 
                ?? $rawFinding['title'] 
                ?? $rawFinding['name'] 
                ?? $rawFinding['description'] 
                ?? 'Security Observation';

            $normalized['severity'] = $this->standardizeSeverity($rawFinding['severity'] ?? $rawFinding['risk'] ?? 'info');
            $normalized['confidence'] = isset($rawFinding['confidence']) ? (float) $rawFinding['confidence'] : 0.85;
            
            if (isset($rawFinding['evidence'])) {
                $normalized['evidence'] = is_array($rawFinding['evidence']) ? $rawFinding['evidence'] : [$rawFinding['evidence']];
            } elseif (isset($rawFinding['output'])) {
                $normalized['evidence'] = is_array($rawFinding['output']) ? $rawFinding['output'] : [$rawFinding['output']];
            }

            $normalized['metadata'] = $rawFinding['metadata'] ?? array_diff_key($rawFinding, array_flip(['finding', 'title', 'name', 'severity', 'risk', 'confidence', 'evidence', 'output']));
        }

        return $normalized;
    }

    /**
     * Normalize an array of multiple findings.
     *
     * @param string $source
     * @param array $findingsList
     * @return array
     */
    public function normalizeList(string $source, array $findingsList): array
    {
        $results = [];
        foreach ($findingsList as $item) {
            $results[] = $this->normalize($source, $item);
        }
        return $results;
    }

    /**
     * Standardize severity levels to: critical, high, medium, low, info.
     */
    public function standardizeSeverity(string $severity): string
    {
        $s = strtolower(trim($severity));
        return match ($s) {
            'critical', 'fatal' => 'critical',
            'high', 'severe' => 'high',
            'medium', 'moderate', 'warning' => 'medium',
            'low', 'minor' => 'low',
            default => 'info',
        };
    }
}
