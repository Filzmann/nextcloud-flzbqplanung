<?php

declare(strict_types=1);

namespace OCP\AppFramework\Utility {
    if (!interface_exists(ITimeFactory::class)) { interface ITimeFactory { public function now(): \DateTimeImmutable; } }
}

namespace FlzBqPlanning\Tests {
    use DateTimeImmutable;
    use InvalidArgumentException;
    use OCA\FlzBqPlanning\Repository\TemporaryAdminAccessRepositoryInterface;
    use OCA\FlzBqPlanning\Service\TemporaryAdminAccessDeniedException;
    use OCA\FlzBqPlanning\Service\TemporaryAdminAccessService;
    use OCP\AppFramework\Utility\ITimeFactory;
    use OCP\IGroup;
    use OCP\IGroupManager;
    use Psr\Log\LoggerInterface;

    final class TemporaryAccessGroups implements IGroupManager {
        public function __construct(public array $admins = [], public array $memberships = []) {}
        public function isAdmin(string $uid): bool { return in_array($uid, $this->admins, true); }
        public function isInGroup(string $uid, string $gid): bool { return in_array($gid, $this->memberships[$uid] ?? [], true); }
        public function get(string $gid): ?IGroup { return null; }
    }

    TestRunner::test('privacy officers alone manage UID-exact bounded grants and rejected actions do not mutate', static function (): void {
        $session=new FrameworkSession(new FrameworkUser('privacy-officer'));
        $groups=new TemporaryAccessGroups(
            admins:['admin-a','admin-b'],
            memberships:['privacy-officer'=>['Datenschutzbeauftragte']],
        );
        $clock=new class implements ITimeFactory { public function now():DateTimeImmutable{return new DateTimeImmutable('2026-08-25T10:00:00+00:00');} };
        $repository=new class implements TemporaryAdminAccessRepositoryInterface {
            public array $rows=[];public int $mutations=0;public bool $fail=false;
            public function replaceActive(string $targetUid,string $grantedBy,DateTimeImmutable $startsAt,DateTimeImmutable $endsAt):array{$this->mutations++;$row=['id'=>1,'targetUid'=>$targetUid,'grantedBy'=>$grantedBy,'startsAt'=>$startsAt,'endsAt'=>$endsAt,'revokedAt'=>null,'revokedBy'=>null];$this->rows=[$row];return $row;}
            public function revokeActive(string $targetUid,string $revokedBy,DateTimeImmutable $revokedAt):bool{if($this->rows===[]||$this->rows[0]['targetUid']!==$targetUid)return false;$this->rows[0]['revokedAt']=$revokedAt;$this->rows[0]['revokedBy']=$revokedBy;$this->mutations++;return true;}
            public function activeFor(string $targetUid,DateTimeImmutable $at):?array{if($this->fail)throw new \RuntimeException();$row=$this->rows[0]??null;return $row!==null&&$row['targetUid']===$targetUid&&$row['revokedAt']===null&&$row['endsAt']>$at?$row:null;}
            public function history():array{return $this->rows;}
        };
        $logger=new class implements LoggerInterface { public array $errors=[];public function warning(string $message,array $context=[]):void{}public function error(string $message,array $context=[]):void{$this->errors[]=$message;}public function info(string $message,array $context=[]):void{} };
        $service=new TemporaryAdminAccessService($session,$groups,$repository,$clock,$logger);
        assertSame(true,$service->canManage());assertSame(false,$service->currentAdminNeedsGrant());assertSame([],$service->state()['history']);
        assertThrows(static fn()=> $service->activate('admin-b',1441),InvalidArgumentException::class);
        assertThrows(static fn()=> $service->activate('ordinary',60),InvalidArgumentException::class);
        assertThrows(static fn()=> $service->revoke('ordinary'),InvalidArgumentException::class);
        assertSame(0,$repository->mutations);
        $grant=$service->activate('admin-b',1440);assertSame('privacy-officer',$grant['grantedBy']);assertSame('2026-08-26T10:00:00+00:00',$grant['endsAt']->format(DATE_ATOM));assertSame(true,$service->hasActiveGrant('admin-b'));assertSame(false,$service->hasActiveGrant('admin-a'));
        $groups->memberships=[];$before=$repository->mutations;assertThrows(static fn()=> $service->revoke('admin-b'),TemporaryAdminAccessDeniedException::class);assertSame($before,$repository->mutations);
        $groups->memberships=['privacy-officer'=>['Datenschutzbeauftragte']];assertSame(true,$service->revoke('admin-b'));assertSame('privacy-officer',$repository->rows[0]['revokedBy']);assertSame(false,$service->hasActiveGrant('admin-b'));
        $repository->fail=true;assertSame(false,$service->hasActiveGrant('admin-b'));assertTrue($logger->errors!==[]);

        foreach (['admin-a','user-a'] as $actorUid) {
            $denied=new TemporaryAdminAccessService(new FrameworkSession(new FrameworkUser($actorUid)),$groups,$repository,$clock,$logger);
            $before=$repository->mutations;
            assertSame(false,$denied->canManage());
            assertThrows(static fn()=> $denied->state(),TemporaryAdminAccessDeniedException::class);
            assertThrows(static fn()=> $denied->activate('admin-b',60),TemporaryAdminAccessDeniedException::class);
            assertThrows(static fn()=> $denied->revoke('admin-b'),TemporaryAdminAccessDeniedException::class);
            assertSame($before,$repository->mutations);
        }
        $nativeAdmin=new TemporaryAdminAccessService(new FrameworkSession(new FrameworkUser('admin-a')),$groups,$repository,$clock,$logger);
        assertSame(true,$nativeAdmin->currentAdminNeedsGrant());
        $groups->memberships['admin-a']=['Datenschutzbeauftragte'];
        assertSame(true,$nativeAdmin->canManage());assertSame(true,$nativeAdmin->currentAdminNeedsGrant());
    });
}
