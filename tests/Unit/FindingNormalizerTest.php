<?php

namespace Tests\Unit;

use App\Models\Application;
use App\Services\Analysis\FindingNormalizer;
use App\Services\Analysis\ResultAggregator;
use PHPUnit\Framework\TestCase;

class FindingNormalizerTest extends TestCase
{
    public function test_normalizes_raw_string_finding(): void
    {
        $normalizer = new FindingNormalizer();
        $res = $normalizer->normalize('frida', 'SSL Pinning bypassed successfully');

        $this->assertEquals('frida', $res['source']);
        $this->assertEquals('SSL Pinning bypassed successfully', $res['finding']);
        $this->assertEquals('info', $res['severity']);
    }

    public function test_normalizes_structured_array_finding(): void
    {
        $normalizer = new FindingNormalizer();
        $raw = [
            'finding' => 'Hardcoded AES Secret Key Found',
            'severity' => 'CRITICAL',
            'confidence' => 0.98,
            'evidence' => ['key_offset' => '0x0045A', 'algorithm' => 'AES/CBC/PKCS5Padding'],
            'cwe' => 'CWE-321',
        ];

        $res = $normalizer->normalize('mobsf', $raw);

        $this->assertEquals('mobsf', $res['source']);
        $this->assertEquals('Hardcoded AES Secret Key Found', $res['finding']);
        $this->assertEquals('critical', $res['severity']);
        $this->assertEquals(0.98, $res['confidence']);
        $this->assertArrayHasKey('cwe', $res['metadata']);
    }

    public function test_result_aggregator_calculates_risk_score_and_levels(): void
    {
        $normalizer = new FindingNormalizer();
        $aggregator = new ResultAggregator($normalizer);

        $app = new Application();
        $app->id = 1;
        $app->name = 'SecureBanking.apk';
        $app->package_name = 'com.bank.secure';
        $app->sha256 = 'abcdef1234567890abcdef1234567890abcdef1234567890abcdef1234567890';

        $toolResults = [
            'frida' => [
                'status' => 'success',
                'findings' => [
                    ['finding' => 'Root Detection Guard Active', 'severity' => 'low'],
                    ['finding' => 'Insecure Key Exchange', 'severity' => 'high'],
                ]
            ],
            'mobsf' => [
                'status' => 'success',
                'findings' => [
                    ['finding' => 'Exported Vulnerable Activity', 'severity' => 'critical'],
                ]
            ]
        ];

        $summary = $aggregator->aggregate($app, $toolResults);

        $this->assertEquals(3, $summary['summary']['total_findings']);
        $this->assertEquals(1, $summary['summary']['severity_counts']['critical']);
        $this->assertEquals(1, $summary['summary']['severity_counts']['high']);
        $this->assertEquals(1, $summary['summary']['severity_counts']['low']);
        $this->assertGreaterThan(50, $summary['summary']['risk_score']);
        $this->assertContains($summary['summary']['risk_level'], ['High', 'Critical']);
    }
}
