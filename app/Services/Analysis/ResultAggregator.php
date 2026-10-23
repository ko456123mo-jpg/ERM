<?php

namespace App\Services\Analysis;

use App\Models\Application;

class ResultAggregator
{
    protected FindingNormalizer $normalizer;

    public function __construct(FindingNormalizer $normalizer)
    {
        $this->normalizer = $normalizer;
    }

    /**
     * Aggregate findings across multiple raw tool outputs.
     *
     * @param Application $application
     * @param array<string, array> $toolResults Keyed by tool name: ['frida' => [...], 'mobsf' => [...]]
     * @return array
     */
    public function aggregate(Application $application, array $toolResults): array
    {
        $unifiedFindings = [];
        $severityCounts = [
            'critical' => 0,
            'high' => 0,
            'medium' => 0,
            'low' => 0,
            'info' => 0,
        ];

        foreach ($toolResults as $source => $result) {
            $findings = $result['findings'] ?? ($result['status'] === 'success' && isset($result['output']) ? $result['output'] : []);
            
            if (is_array($findings)) {
                foreach ($findings as $finding) {
                    $normalized = $this->normalizer->normalize($source, $finding);
                    $unifiedFindings[] = $normalized;
                    $severityCounts[$normalized['severity']]++;
                }
            }
        }

        $riskScore = $this->calculateRiskScore($severityCounts);

        return [
            'application' => [
                'id' => $application->id,
                'name' => $application->name,
                'package_name' => $application->package_name,
                'sha256' => $application->sha256,
            ],
            'summary' => [
                'total_findings' => count($unifiedFindings),
                'risk_score' => $riskScore,
                'risk_level' => $this->determineRiskLevel($riskScore),
                'severity_counts' => $severityCounts,
            ],
            'findings' => $unifiedFindings,
            'generated_at' => date('c'),
        ];
    }

    /**
     * Calculate normalized 0 - 100 risk score based on finding weights.
     */
    protected function calculateRiskScore(array $severityCounts): int
    {
        $weights = [
            'critical' => 35,
            'high' => 20,
            'medium' => 10,
            'low' => 3,
            'info' => 0,
        ];

        $rawScore = (
            $severityCounts['critical'] * $weights['critical'] +
            $severityCounts['high'] * $weights['high'] +
            $severityCounts['medium'] * $weights['medium'] +
            $severityCounts['low'] * $weights['low']
        );

        return min(100, $rawScore);
    }

    /**
     * Categorize risk score into Critical / High / Medium / Low / Safe.
     */
    protected function determineRiskLevel(int $score): string
    {
        if ($score >= 75) return 'Critical';
        if ($score >= 50) return 'High';
        if ($score >= 25) return 'Medium';
        if ($score > 0) return 'Low';
        return 'Safe';
    }
}
