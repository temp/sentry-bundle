<?php

declare(strict_types=1);

namespace Temp\SentryBundleTests\EventListener;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sentry\State\HubInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\User;
use Temp\SentryBundle\EventListener\SentryUserListener;

/**
 * @covers \Temp\SentryBundle\EventListener\SentryUserListener
 */
final class SentryUserListenerTest extends TestCase
{
    use ProphecyTrait;
    use HubExpections;

    /** @var ObjectProphecy|HubInterface  */
    private $hub;
    /** @var ObjectProphecy|Security  */
    private $security;
    private SentryUserListener $listener;

    public function setUp(): void
    {
        $this->hub = $this->prophesize(HubInterface::class);
        $this->security = $this->prophesize(Security::class);

        $this->listener = new SentryUserListener($this->hub->reveal(), $this->security->reveal());
    }

    public function testNoOnKernelRequestEventHandlingForNonMasterRequest(): void
    {
        $kernel = $this->prophesize(Kernel::class);
        $event = new RequestEvent($kernel->reveal(), new Request(), null);

        $this->expectHubIsNotConfigured($this->hub);

        $this->listener->onKernelRequest($event);
    }

    public function testScopeIsConfigured(): void
    {
        $kernel = $this->prophesize(Kernel::class);
        $event = new RequestEvent($kernel->reveal(), new Request(), HttpKernelInterface::MASTER_REQUEST);

        $this->expectHubIsConfigured($this->hub);

        $this->listener->onKernelRequest($event);
    }

    public function testUserDataIsConfigured(): void
    {
        $kernel = $this->prophesize(Kernel::class);
        $request = new Request([], [], [], [], [], ['REMOTE_ADDR' => '1.2.3.4']);
        $event = new RequestEvent($kernel->reveal(), $request, HttpKernelInterface::MASTER_REQUEST);

        $this->security->getUser()
            ->willReturn(new User('foo', 'bar', ['role1', 'role2']));

        $this->expectHubIsConfiguredWithValues($this->hub, 'user', [
            'username' => 'foo',
            'ip_address' => '1.2.3.4',
            'type' => 'User',
            'roles' => ['role1', 'role2'],
        ]);

        $this->listener->onKernelRequest($event);
    }

    public function testGetSubscribedEvents(): void
    {
        $this->assertSame(
            [KernelEvents::REQUEST => ['onKernelRequest', 1]],
            SentryUserListener::getSubscribedEvents(),
        );
    }
}
