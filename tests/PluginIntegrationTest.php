<?php

declare(strict_types=1);

namespace Be\PsalmPlugin\Tests;

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

use function array_filter;
use function array_values;
use function dirname;
use function escapeshellarg;
use function file_exists;
use function in_array;
use function is_array;
use function json_decode;
use function shell_exec;
use function sprintf;
use function str_contains;
use function str_ends_with;
use function str_replace;

/**
 * Black-box integration test for the Psalm plugin
 *
 * Runs the actual `vendor/bin/psalm` binary against `tests/Psalm/Fixture/psalm.xml`
 * with the plugin enabled, parses the JSON output, and asserts that the expected
 * issues are reported on the expected files (and that valid fixtures stay clean).
 */
final class PluginIntegrationTest extends TestCase
{
    /** @var list<array<string, mixed>>|null */
    private static array|null $cachedIssues = null;

    /** @var list<array<string, mixed>>|null */
    private static array|null $cachedTaintIssues = null;

    #[TestDox('MissingBeingParameterAttribute is reported when no #[Input]/#[Inject] is present')]
    public function testMissingInputAndInjectIsReported(): void
    {
        $this->assertIssue(
            'MissingBeingParameterAttribute',
            'BadBeingMissingInputAndInject.php',
        );
    }

    #[TestDox('MissingBeingParameterAttribute is reported on partially covered Being constructors')]
    public function testPartialCoverageIsReported(): void
    {
        $this->assertIssue(
            'MissingBeingParameterAttribute',
            'BadBeingPartialCoverage.php',
        );
    }

    #[TestDox('ConflictingBeingParameterAttribute is reported when #[Input] and #[Inject] are both present')]
    public function testConflictingInputAndInjectIsReported(): void
    {
        $this->assertIssue(
            'ConflictingBeingParameterAttribute',
            'BadBeingConflictingAttributes.php',
        );
    }

    #[TestDox('InvalidValidateException is reported on RuntimeException-based throws')]
    public function testValidatorThrowingRuntimeExceptionIsReported(): void
    {
        $this->assertIssue(
            'InvalidValidateException',
            'BadValidatorRuntimeException.php',
        );
    }

    #[TestDox('InvalidValidateException is reported on conditional non-DomainException throws')]
    public function testConditionalNonDomainExceptionIsReported(): void
    {
        $this->assertIssue(
            'InvalidValidateException',
            'BadValidatorConditionalThrow.php',
        );
    }

    #[TestDox('InvalidValidateException is reported on variable throws of non-DomainException types')]
    public function testThrowsVariableIsReported(): void
    {
        $this->assertIssue(
            'InvalidValidateException',
            'BadValidatorThrowsVariable.php',
        );
    }

    #[TestDox('Valid Being and Validator fixtures produce no plugin issues')]
    public function testValidFixturesProduceNoIssues(): void
    {
        $issues = self::issues();
        $invalid = array_values(array_filter($issues, static function (array $i): bool {
            $fileName = str_replace('\\', '/', (string) ($i['file_name'] ?? ''));

            return str_contains($fileName, 'Valid/')
                && in_array(
                    (string) ($i['type'] ?? ''),
                    [
                        'MissingBeingParameterAttribute',
                        'ConflictingBeingParameterAttribute',
                        'InvalidValidateException',
                    ],
                    true,
                );
        }));
        $this->assertSame([], $invalid, 'Valid fixtures should not produce plugin issues');
    }

    #[TestDox('TaintedHtml is reported when a promoted #[Input] property is echoed')]
    public function testPromotedInputPropertyIsTainted(): void
    {
        $this->assertTaintIssue(
            'TaintedHtml',
            'TaintedPromotedInput.php',
        );
    }

    #[TestDox('TaintedHtml is reported when a promoted #[Input] property is echoed from an object')]
    public function testPromotedInputObjectPropertyIsTainted(): void
    {
        $this->assertTaintIssue(
            'TaintedHtml',
            'TaintedPromotedInputObject.php',
        );
    }

    #[TestDox('TaintedHtml is reported when an assigned #[Input] property is echoed')]
    public function testAssignedInputPropertyIsTainted(): void
    {
        $this->assertTaintIssue(
            'TaintedHtml',
            'TaintedAssignedInput.php',
        );
    }

    #[TestDox('#[Inject] properties are not treated as taint sources')]
    public function testInjectedPropertyIsNotTainted(): void
    {
        $this->assertNoTaintIssue(
            'TaintedHtml',
            'InjectedNotTainted.php',
        );
    }

    #[TestDox('Sanitized values are not re-tainted by downstream #[Input] constructors')]
    public function testSanitizedReinputIsNotTaintedAgain(): void
    {
        $this->assertNoTaintIssue(
            'TaintedHtml',
            'SanitizedReinput.php',
        );
    }

    private function assertIssue(string $type, string $fileSuffix): void
    {
        foreach (self::issues() as $issue) {
            if (
                ($issue['type'] ?? null) === $type
                && str_ends_with((string) ($issue['file_name'] ?? ''), $fileSuffix)
            ) {
                $this->addToAssertionCount(1);

                return;
            }
        }

        $this->fail(sprintf('Expected %s on %s but did not find it in psalm output', $type, $fileSuffix));
    }

    private function assertTaintIssue(string $type, string $fileSuffix): void
    {
        foreach (self::taintIssues() as $issue) {
            if (
                ($issue['type'] ?? null) === $type
                && str_ends_with((string) ($issue['file_name'] ?? ''), $fileSuffix)
            ) {
                $this->addToAssertionCount(1);

                return;
            }
        }

        $this->fail(sprintf('Expected %s on %s but did not find it in psalm taint output', $type, $fileSuffix));
    }

    private function assertNoTaintIssue(string $type, string $fileSuffix): void
    {
        foreach (self::taintIssues() as $issue) {
            if (
                ($issue['type'] ?? null) === $type
                && str_ends_with((string) ($issue['file_name'] ?? ''), $fileSuffix)
            ) {
                $this->fail(sprintf('Did not expect %s on %s in psalm taint output', $type, $fileSuffix));
            }
        }

        $this->addToAssertionCount(1);
    }

    /** @return list<array<string, mixed>> */
    private static function issues(): array
    {
        if (self::$cachedIssues !== null) {
            return self::$cachedIssues;
        }

        self::$cachedIssues = self::runPsalm();

        return self::$cachedIssues;
    }

    /** @return list<array<string, mixed>> */
    private static function taintIssues(): array
    {
        if (self::$cachedTaintIssues !== null) {
            return self::$cachedTaintIssues;
        }

        self::$cachedTaintIssues = self::runPsalm('--taint-analysis');

        return self::$cachedTaintIssues;
    }

    /** @return list<array<string, mixed>> */
    private static function runPsalm(string $extraArgs = ''): array
    {
        $root = dirname(__DIR__);
        $psalmBin = $root . '/vendor/bin/psalm';
        $config = __DIR__ . '/Fixture/psalm.xml';

        if (! file_exists($psalmBin)) {
            self::markTestSkippedWithReason('vendor/bin/psalm not found; run composer install');
        }

        if (! file_exists($config)) {
            self::markTestSkippedWithReason('fixture psalm.xml missing: ' . $config);
        }

        $cmd = sprintf(
            '%s --config=%s %s --output-format=json --no-cache --no-progress 2>/dev/null',
            escapeshellarg($psalmBin),
            escapeshellarg($config),
            $extraArgs,
        );

        $stdout = shell_exec($cmd);
        $stdout = $stdout === null || $stdout === false ? '[]' : $stdout;

        $decoded = json_decode($stdout, true);
        if (! is_array($decoded)) {
            self::fail('Failed to parse Psalm JSON output: ' . $stdout);
        }

        /** @var list<array<string, mixed>> $decoded */
        return $decoded;
    }

    private static function markTestSkippedWithReason(string $reason): never
    {
        self::markTestSkipped($reason);
    }
}
