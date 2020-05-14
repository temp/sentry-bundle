<?php

declare(strict_types=1);

namespace Temp\SentryBundleTests\EventListener;

use Exception;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\Test\TestLogger;
use Sentry\State\HubInterface;
use Symfony\Component\Console\Command\HelpCommand;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;
use Temp\SentryBundle\EventListener\SentryConsoleListener;

/**
 * @covers \Temp\SentryBundle\EventListener\SentryConsoleListener
 */
final class SentryConsoleListenerTest extends TestCase
{
    use HubExpections;
    use ProphecyTrait;

    /** @var ObjectProphecy|HubInterface  */
    private $hub;
    private TestLogger $logger;
    private SentryConsoleListener $listener;

    public function setUp(): void
    {
        $this->hub = $this->prophesize(HubInterface::class);
        $this->logger = new TestLogger();

        $this->listener = new SentryConsoleListener($this->hub->reveal(), $this->logger);
    }

    public function testScopeIsConfiguredForOnConsoleCommand(): void
    {
        $event = new ConsoleCommandEvent(null, new StringInput('foo'), new NullOutput());

        $this->expectHubIsConfigured($this->hub);

        $this->listener->onConsoleCommand($event);
    }

    public function testTagsDataIsConfiguredForOnConsoleCommand(): void
    {
        $event = new ConsoleCommandEvent(new HelpCommand(), new StringInput('foo'), new NullOutput());

        $this->expectHubIsConfiguredWithValues($this->hub, 'tags', ['command' => 'help']);

        $this->listener->onConsoleCommand($event);
    }

    public function testScopeIsConfiguredForOnConsoleError(): void
    {
        $event = new ConsoleErrorEvent(new StringInput('foo'), new NullOutput(), new Exception('foo'), null);

        $this->expectHubIsConfigured($this->hub);

        $this->listener->onConsoleError($event);
    }

    public function testTagsDataIsConfiguredForOnConsoleError(): void
    {
        $event = new ConsoleErrorEvent(new StringInput('foo'), new NullOutput(), new Exception('foo'), null);
        $event->setExitCode(127);

        $this->expectHubIsConfiguredWithValues($this->hub, 'tags', ['exit_code' => '127']);

        $this->listener->onConsoleError($event);
    }

    public function testLogsAreCreatedForOnConsoleError(): void
    {
        $event = new ConsoleErrorEvent(new StringInput('foo'), new NullOutput(), new Exception('foo'), null);

        $this->listener->onConsoleError($event);

        $this->assertTrue($this->logger->hasCriticalRecords());
    }

    public function testNoOnConsoleTerminateHandlingForZeroExitCode(): void
    {
        $event = new ConsoleTerminateEvent(new HelpCommand(), new StringInput('foo'), new NullOutput(), 0);

        $this->listener->onConsoleTerminate($event);

        $this->assertFalse($this->logger->hasWarningRecords());
    }

    public function testLogsAreCreatedForOnConsoleTerminate(): void
    {
        $event = new ConsoleTerminateEvent(new HelpCommand(), new StringInput('foo'), new NullOutput(), 100);

        $this->listener->onConsoleTerminate($event);

        $this->assertTrue($this->logger->hasWarningThatContains('100'));
    }

    public function testExitCodeIsFixedForOnConsoleTerminate(): void
    {
        $event = new ConsoleTerminateEvent(new HelpCommand(), new StringInput('foo'), new NullOutput(), 1000);

        $this->listener->onConsoleTerminate($event);

        $this->assertTrue($this->logger->hasWarningThatContains('255'));
    }

    public function testCommandNameIsLoggedForOnConsoleTerminate(): void
    {
        $event = new ConsoleTerminateEvent(new HelpCommand(), new StringInput('foo'), new NullOutput(), 1);

        $this->listener->onConsoleTerminate($event);

        $this->assertTrue($this->logger->hasWarningThatContains('help'));
    }

    public function testGetSubscribedEvents(): void
    {
        $this->assertSame(
            [
                ConsoleEvents::COMMAND => ['onConsoleCommand', 1],
                ConsoleEvents::ERROR => ['onConsoleError', 1],
                ConsoleEvents::TERMINATE => ['onConsoleTerminate', 1],
            ],
            SentryConsoleListener::getSubscribedEvents(),
        );
    }
}
