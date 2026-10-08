<?php

declare(strict_types=1);

namespace OCA\FlzBqPlanning\Privacy;

interface BqPrivacySource {
    /** @return list<array<string, mixed>> */
    public function forSubject(string $uid, int $limit): array;
}
