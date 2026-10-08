<?php

declare(strict_types=1);

namespace FlzBqPlanning\Tests;

use RuntimeException;
use Throwable;

final class TestRunner {
    private static int $passed = 0;
    private static int $failed = 0;

    public static function test(string $name, callable $test): void {
        try {
            $test();
            self::$passed++;
            fwrite(STDOUT, "PASS {$name}\n");
        } catch (Throwable $error) {
            self::$failed++;
            fwrite(STDERR, "FAIL {$name}: {$error->getMessage()}\n");
        }
    }

    public static function finish(): never {
        fwrite(STDOUT, sprintf("%d passed, %d failed\n", self::$passed, self::$failed));
        exit(self::$failed === 0 ? 0 : 1);
    }
}

function assertSame(mixed $expected, mixed $actual, string $message = ''): void {
    if ($expected !== $actual) {
        throw new RuntimeException($message !== '' ? $message : sprintf(
            'Expected %s, got %s',
            var_export($expected, true),
            var_export($actual, true),
        ));
    }
}

function assertTrue(bool $actual, string $message = 'Expected true'): void {
    if (!$actual) {
        throw new RuntimeException($message);
    }
}

function assertThrows(callable $callback, string $exceptionClass): void {
    try {
        $callback();
    } catch (Throwable $error) {
        if ($error instanceof $exceptionClass) {
            return;
        }
        throw new RuntimeException('Unexpected exception ' . $error::class . ': ' . $error->getMessage());
    }
    throw new RuntimeException('Expected exception ' . $exceptionClass);
}
