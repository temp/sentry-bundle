<?php

declare(strict_types=1);

namespace Temp\SentryBundle\EventListener;

use Monolog\ResettableInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\Event\WorkerMessageHandledEvent;
use function get_class;

final class MonologResetterEventListener implements EventSubscriberInterface
{
    /** @var LoggerInterface&ResettableInterface */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        if (!$logger instanceof ResettableInterface) {
            throw new RuntimeException('Logger needs to be resettable');
        }

        $this->logger = $logger;
    }

    public function onMessageFailed(WorkerMessageFailedEvent $event): void
    {
        $message = $event->getEnvelope()->getMessage();

        $context = [
            'message' => $message,
            'error' => $event->getThrowable()->getMessage(),
            'class' => get_class($message),
            'exception' => $event->getThrowable(),
        ];

        $this->logger->error('Error thrown while handling message {class}. Error: "{error}"', $context);

        $this->resetLogger();
    }

    public function onMessageHandled(WorkerMessageHandledEvent $event): void
    {
        $this->resetLogger();
    }

    private function resetLogger(): void
    {
        $this->logger->reset();
    }

    /**
     * @return mixed[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            // It should be called after
            // \Symfony\Component\Messenger\EventListener\SendFailedMessageToFailureTransportListener
            // So that we have as much information as we can
            WorkerMessageFailedEvent::class => ['onMessageFailed', -200],
            WorkerMessageHandledEvent::class => 'onMessageHandled',
        ];
    }
}
