<?php

declare(strict_types=1);

namespace OCP\AppFramework\Utility {
    if (!interface_exists(ITimeFactory::class)) { interface ITimeFactory { public function now(): \DateTimeImmutable; } }
}

namespace AdBqPlanning\Tests {
    use DateTimeImmutable;
    use InvalidArgumentException;
    use OCA\AdBqPlanning\Repository\TemporaryAdminAccessRepositoryInterface;
    use OCA\AdBqPlanning\Service\TemporaryAdminAccessDeniedException;
    use OCA\AdBqPlanning\Service\TemporaryAdminAccessService;
    use OCP\AppFramework\Utility\ITimeFactory;
    use Psr\Log\LoggerInterface;

    TestRunner::test('temporary admin grant is UID-exact bounded revocable and fail-closed', static function (): void {
        $session=new FrameworkSession(new FrameworkUser('admin-a'));
        $groups=new FrameworkGroups(true);
        $clock=new class implements ITimeFactory { public function now():DateTimeImmutable{return new DateTimeImmutable('2026-08-25T10:00:00+00:00');} };
        $repository=new class implements TemporaryAdminAccessRepositoryInterface {
            public array $rows=[];public int $mutations=0;public bool $fail=false;
            public function replaceActive(string $targetUid,string $grantedBy,DateTimeImmutable $startsAt,DateTimeImmutable $endsAt):array{$this->mutations++;$row=['id'=>1,'targetUid'=>$targetUid,'grantedBy'=>$grantedBy,'startsAt'=>$startsAt,'endsAt'=>$endsAt,'revokedAt'=>null,'revokedBy'=>null];$this->rows=[$row];return $row;}
            public function revokeActive(string $targetUid,string $revokedBy,DateTimeImmutable $revokedAt):bool{if($this->rows===[])return false;$this->rows[0]['revokedAt']=$revokedAt;$this->mutations++;return true;}
            public function activeFor(string $targetUid,DateTimeImmutable $at):?array{if($this->fail)throw new \RuntimeException();$row=$this->rows[0]??null;return $row!==null&&$row['targetUid']===$targetUid&&$row['revokedAt']===null&&$row['endsAt']>$at?$row:null;}
            public function history():array{return $this->rows;}
        };
        $logger=new class implements LoggerInterface { public array $errors=[];public function warning(string $message,array $context=[]):void{}public function error(string $message,array $context=[]):void{$this->errors[]=$message;}public function info(string $message,array $context=[]):void{} };
        $service=new TemporaryAdminAccessService($session,$groups,$repository,$clock,$logger);
        assertThrows(static fn()=> $service->activate('admin-b',1441),InvalidArgumentException::class);assertSame(0,$repository->mutations);
        $grant=$service->activate('admin-b',1440);assertSame('2026-08-26T10:00:00+00:00',$grant['endsAt']->format(DATE_ATOM));assertSame(true,$service->hasActiveGrant('admin-b'));assertSame(false,$service->hasActiveGrant('admin-a'));
        assertSame(true,$service->revoke('admin-b'));assertSame(false,$service->hasActiveGrant('admin-b'));
        $repository->fail=true;assertSame(false,$service->hasActiveGrant('admin-b'));assertTrue($logger->errors!==[]);
        $ordinary=new TemporaryAdminAccessService(new FrameworkSession(new FrameworkUser('user-a')),new FrameworkGroups(false),$repository,$clock,$logger);
        assertThrows(static fn()=> $ordinary->activate('admin-b',60),TemporaryAdminAccessDeniedException::class);
    });
}
