<?php

declare(strict_types=1);

namespace AdBqPlanning\Tests;

use DomainException;
use OCA\AdBqPlanning\Domain\Curriculum\CurriculumSnapshot;
use OCA\AdBqPlanning\Domain\Curriculum\CurriculumTemplate;
use OCA\AdBqPlanning\Domain\Lecturer\LecturerProfile;
use OCA\AdBqPlanning\Domain\Lecturer\TeachingTeamPlan;

TestRunner::test('run curriculum is a movable snapshot and never mutates its template', static function (): void {
    $template = new CurriculumTemplate('basis-2026', 3, [
        ['id' => 'pflege-1', 'title' => 'Pflege 1', 'minutes' => 180],
        ['id' => 'pflege-2', 'title' => 'Pflege 2', 'minutes' => 180],
        ['id' => 'datenschutz', 'title' => 'Datenschutz', 'minutes' => 90],
    ]);

    $snapshot = CurriculumSnapshot::fromTemplate($template)
        ->exchange('pflege-1', 'datenschutz')
        ->schedule('datenschutz', '2026-09-11', '09:00', '10:30');

    assertSame(['pflege-1', 'pflege-2', 'datenschutz'], array_column($template->modules, 'id'));
    assertSame(['datenschutz', 'pflege-2', 'pflege-1'], array_column($snapshot->modules(), 'id'));
    assertSame('2026-09-11', $snapshot->module('datenschutz')['date']);
    assertSame(3, $snapshot->templateVersion);
    assertThrows(static fn () => $snapshot->exchange('pflege-1', 'unbekannt'), DomainException::class);
    assertThrows(static fn () => $snapshot->schedule('pflege-1', '2026-09-11', '10:00', '09:00'), DomainException::class);
    assertThrows(static fn () => new CurriculumTemplate('', 1, []), DomainException::class);
    assertThrows(
        static fn () => new CurriculumTemplate('doppelt', 1, [
            ['id' => 'modul', 'title' => 'A', 'minutes' => 30],
            ['id' => 'modul', 'title' => 'B', 'minutes' => 30],
        ]),
        DomainException::class,
    );
});

TestRunner::test('internal lead lecturer covers modules unless a pool lecturer overrides one', static function (): void {
    $lead = new LecturerProfile('nc:pfk-1', 'internal_pfk', 'Interne PFK');
    $external = new LecturerProfile('ext:privacy-1', 'external', 'Externe Dozentin');
    $plan = new TeachingTeamPlan($lead);
    $changed = $plan->assign('datenschutz', $external);

    assertSame('nc:pfk-1', $changed->lecturerFor('pflege-1')->id);
    assertSame('ext:privacy-1', $changed->lecturerFor('datenschutz')->id);
    assertSame('nc:pfk-1', $plan->lecturerFor('datenschutz')->id);
    assertThrows(
        static fn () => new TeachingTeamPlan($external),
        DomainException::class,
    );
    assertThrows(static fn () => new LecturerProfile('', 'internal_pfk', 'PFK'), DomainException::class);
    assertThrows(static fn () => new LecturerProfile('x', 'unknown', 'PFK'), DomainException::class);
    assertThrows(static fn () => $plan->assign(' ', $external), DomainException::class);
});
