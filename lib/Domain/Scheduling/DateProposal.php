<?php

declare(strict_types=1);

namespace OCA\AdBqPlanning\Domain\Scheduling;

final class DateProposal {
    /**
     * @param list<string> $courseDays
     * @param list<array{startsOn:string,conflicts:list<array{date:string,type:string,label:string}>}> $rejectedCandidates
     */
    public function __construct(
        public readonly string $startsOn,
        public readonly string $endsOn,
        public readonly array $courseDays,
        public readonly array $rejectedCandidates,
    ) {
    }
}
