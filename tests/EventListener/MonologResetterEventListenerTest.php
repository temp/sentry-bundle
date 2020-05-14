<?php

declare(strict_types=1);

namespace Temp\SentryBundleTests\EventListener;

use Monolog\ResettableInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;
use RuntimeException;
use stdClass;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\Event\WorkerMessageHandledEvent;
use Temp\SentryBundle\EventListener\MonologResetterEventListener;

/**
 * @covers \Temp\SentryBundle\EventListener\MonologResetterEventListenerTest
 */
final class MonologResetterEventListenerTest extends TestCase
{
    use HubExpections;
    use ProphecyTrait;

    public function testLoggerNeedsToBeResettable(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Logger needs to be resettable');

        $logger = $this->prophesize(LoggerInterface::class);

        $listener = new MonologResetterEventListener($logger->reveal());
    }

    public function testLoggerIsResetOnHandledMessage(): void
    {
        $event = new WorkerMessageHandledEvent(new Envelope(new stdClass()), 'foo');

        $logger = $this->prophesize(LoggerInterface::class)
            ->willImplement(ResettableInterface::class);

        $logger->reset()
            ->shouldBeCalled();

        $listener = new MonologResetterEventListener($logger->reveal());
        $listener->onMessageHandled($event);
    }

    public function testLoggerIsResetOnFailedMessage(): void
    {
        $event = new WorkerMessageFailedEvent(new Envelope(new stdClass()), 'foo', new RuntimeException());

        $logger = $this->prophesize(LoggerInterface::class)
            ->willImplement(ResettableInterface::class);

        $logger->error(Argument::cetera())
            ->shouldBeCalled();

        $logger->reset()
            ->shouldBeCalled();

        $listener = new MonologResetterEventListener($logger->reveal());
        $listener->onMessageFailed($event);
    }

    public function testGetSubscribedEvents(): void
    {
        $this->assertSame(
            [
                WorkerMessageFailedEvent::class => ['onMessageFailed', -200],
                WorkerMessageHandledEvent::class => 'onMessageHandled',
            ],
            MonologResetterEventListener::getSubscribedEvents(),
        );
    }
}
