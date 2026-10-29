<?php

declare(strict_types=1);

namespace FlzBqPlanning\Tests;

use OCA\FlzBqPlanning\Contract\OrganizationSnapshotSource;
use OCA\FlzBqPlanning\Service\OrganizationRolePolicy;
use OCP\IGroup;
use OCP\IGroupManager;
use Psr\Log\LoggerInterface;

final class OrganizationRoleGroups implements IGroupManager {
    /** @param list<string> $existing */
    public function __construct(private array $existing) {}
    public function isAdmin(string $uid): bool { return false; }
    public function isInGroup(string $uid, string $gid): bool { return false; }
    public function get(string $gid): ?IGroup {
        return in_array($gid, $this->existing, true) ? new class implements IGroup {} : null;
    }
}

final class OrganizationRoleSource implements OrganizationSnapshotSource {
    /** @param array<string,mixed>|null $snapshot */
    public function __construct(private ?array $snapshot) {}
    public function snapshot(): ?array { return $this->snapshot; }
}

final class OrganizationRoleLogger implements LoggerInterface {
    /** @var list<array{message:string,context:array<string,mixed>}> */
    public array $warnings = [];
    public function warning(string $message, array $context = []): void {
        $this->warnings[] = ['message' => $message, 'context' => $context];
    }
    public function error(string $message, array $context = []): void {}
}

final class FailingOrganizationRoleGroups implements IGroupManager {
    public function isAdmin(string $uid): bool { return false; }
    public function isInGroup(string $uid, string $gid): bool { return false; }
    public function get(string $gid): ?IGroup { throw new \RuntimeException('group backend unavailable'); }
}

/** @return array<string,mixed> */
function bqOrganizationSnapshot(): array {
    return [
        'contractVersion' => '1.0',
        'valid' => true,
        'roles' => [
            'deputy_pdl' => ['groupId' => 'flz-StvPDL', 'label' => 'Stellvertretende Pflegedienstleitung'],
            'pdl' => ['groupId' => 'flz-PDL', 'label' => 'Pflegedienstleitung'],
            'pfk' => ['groupId' => 'flz-PFK', 'label' => 'Pflegefachkräfte'],
            'finance' => ['groupId' => 'flz-Finanzen', 'label' => 'Finanzen'],
        ],
        'areas' => [],
    ];
}

TestRunner::test('BQ role policy resolves only authorized semantic roles through the current snapshot', static function (): void {
    $policy = new OrganizationRolePolicy(
        new OrganizationRoleSource(bqOrganizationSnapshot()),
        new OrganizationRoleGroups(['flz-StvPDL', 'flz-PDL', 'flz-PFK', 'flz-Finanzen']),
    );

    assertSame('flz-StvPDL', $policy->groupIdForRole('deputy_pdl'));
    assertSame('flz-PDL', $policy->groupIdForRole('pdl'));
    assertSame('flz-PFK', $policy->groupIdForRole('pfk'));
    assertSame(null, $policy->groupIdForRole('finance'));
    assertSame(['deputy_pdl', 'pdl', 'pfk'], array_column($policy->state()['options'], 'roleKey'));
});

TestRunner::test('BQ role policy fails closed for missing invalid or incomplete snapshots and missing groups', static function (): void {
    foreach ([
        null,
        ['contractVersion' => '2.0', 'valid' => true, 'roles' => [], 'areas' => []],
        ['contractVersion' => '1.0', 'valid' => true, 'roles' => ['pfk' => ['groupId' => '', 'label' => 'PFK']], 'areas' => []],
    ] as $snapshot) {
        $policy = new OrganizationRolePolicy(
            new OrganizationRoleSource($snapshot),
            new OrganizationRoleGroups(['flz-StvPDL', 'flz-PDL', 'flz-PFK']),
        );
        assertSame(false, $policy->state()['available']);
        assertSame(null, $policy->groupIdForRole('deputy_pdl'));
    }

    $missingGroup = new OrganizationRolePolicy(
        new OrganizationRoleSource(bqOrganizationSnapshot()),
        new OrganizationRoleGroups(['flz-PDL', 'flz-PFK']),
    );
    assertSame(null, $missingGroup->groupIdForRole('deputy_pdl'));
    assertSame(false, $missingGroup->state()['options'][0]['exists']);
});

TestRunner::test('BQ role policy logs provider failures and keeps permissions disabled', static function (): void {
    $logger = new OrganizationRoleLogger();
    $policy = new OrganizationRolePolicy(
        new OrganizationRoleSource(bqOrganizationSnapshot()),
        new FailingOrganizationRoleGroups(),
        $logger,
    );

    assertSame(false, $policy->state()['available']);
    assertSame(null, $policy->groupIdForRole('deputy_pdl'));
    assertSame(1, count($logger->warnings));
    assertSame(\RuntimeException::class, $logger->warnings[0]['context']['exceptionClass']);
});
