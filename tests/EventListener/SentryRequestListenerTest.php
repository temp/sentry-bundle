<?php

declare(strict_types=1);

namespace Temp\SentryBundleTests\EventListener;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\Test\TestLogger;
use Sentry\State\HubInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\KernelEvents;
use Temp\SentryBundle\EventListener\SentryRequestListener;

/**
 * @covers \Temp\SentryBundle\EventListener\SentryRequestListener
 */
final class SentryRequestListenerTest extends TestCase
{
    use HubExpections;
    use ProphecyTrait;

    /** @var ObjectProphecy|HubInterface  */
    private $hub;
    private TestLogger $logger;
    private SentryRequestListener $listener;

    public function setUp(): void
    {
        $this->hub = $this->prophesize(HubInterface::class);
        $this->logger = new TestLogger();

        $this->listener = new SentryRequestListener($this->hub->reveal(), $this->logger);
    }

    public function testNoOnKernelControllerHandlingForNonMasterRequest(): void
    {
        $kernel = $this->prophesize(Kernel::class);
        $event = new ControllerEvent(
            $kernel->reveal(),
            static function (): void {
            },
            new Request(),
            null,
        );

        $this->expectHubIsNotConfigured($this->hub);

        $this->listener->onKernelController($event);
    }

    public function testNoOnKernelControllerHandlingForMissingRoute(): void
    {
        $kernel = $this->prophesize(Kernel::class);
        $event = new ControllerEvent(
            $kernel->reveal(),
            static function (): void {
            },
            new Request(),
            HttpKernelInterface::MASTER_REQUEST,
        );

        $this->expectHubIsNotConfigured($this->hub);

        $this->listener->onKernelController($event);
    }

    public function testScopeIsConfiguredForOnKernelController(): void
    {
        $kernel = $this->prophesize(Kernel::class);
        $event = new ControllerEvent(
            $kernel->reveal(),
            static function (): void {
            },
            new Request([], [], ['_route' => 'foo']),
            HttpKernelInterface::MASTER_REQUEST,
        );

        $this->expectHubIsConfigured($this->hub);

        $this->listener->onKernelController($event);
    }

    public function testTagsDataIsConfiguredForOnKernelController(): void
    {
        $kernel = $this->prophesize(Kernel::class);
        $event = new ControllerEvent(
            $kernel->reveal(),
            static function (): void {
            },
            new Request([], [], ['_route' => 'foo']),
            HttpKernelInterface::MASTER_REQUEST,
        );

        $this->expectHubIsConfiguredWithValues($this->hub, 'tags', ['route' => 'foo']);

        $this->listener->onKernelController($event);
    }

    public function testScopeIsConfiguredForOnKernelTerminate(): void
    {
        $kernel = $this->prophesize(Kernel::class);
        $event = new TerminateEvent($kernel->reveal(), new Request(), new Response(''));

        $this->expectHubIsConfigured($this->hub);

        $this->listener->onKernelTerminate($event);
    }

    public function testTagsDataIsConfiguredForOnKernelTerminate(): void
    {
        $kernel = $this->prophesize(Kernel::class);
        $event = new TerminateEvent(
            $kernel->reveal(),
            new Request([], [], ['_route' => 'foo']),
            new Response('', 200),
        );

        $this->expectHubIsConfiguredWithValues($this->hub, 'tags', ['status_code' => '200']);

        $this->listener->onKernelTerminate($event);
    }

    public function testNoLogsAreCreatedOnHttpStatusCodeOkForOnKernelTerminate(): void
    {
        $kernel = $this->prophesize(Kernel::class);
        $event = new TerminateEvent(
            $kernel->reveal(),
            new Request([], [], ['_route' => 'foo']),
            new Response('', Response::HTTP_OK),
        );

        $this->listener->onKernelTerminate($event);
        $this->assertFalse($this->logger->hasErrorRecords());
    }

    public function testLogsAreCreatedOnHttpStatusCodeInternalServerErrorForOnKernelTerminate(): void
    {
        $kernel = $this->prophesize(Kernel::class);
        $event = new TerminateEvent(
            $kernel->reveal(),
            new Request([], [], ['_route' => 'foo']),
            new Response('', Response::HTTP_INTERNAL_SERVER_ERROR),
        );

        $this->listener->onKernelTerminate($event);
        $this->assertTrue($this->logger->hasErrorRecords());
    }

    public function testGetSubscribedEvents(): void
    {
        $this->assertSame(
            [
                KernelEvents::CONTROLLER => ['onKernelController', 10000],
                KernelEvents::TERMINATE => ['onKernelTerminate', 1],
            ],
            SentryRequestListener::getSubscribedEvents(),
        );
    }
}
