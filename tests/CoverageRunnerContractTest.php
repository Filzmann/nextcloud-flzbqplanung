<?php

declare(strict_types=1);

namespace FlzBqPlanning\Tests;

TestRunner::test('the test runner returns control so the coverage tool can write its report', function (): void {
    $returnType = (new \ReflectionMethod(TestRunner::class, 'finish'))->getReturnType();

    assertSame('void', $returnType?->getName());
});
