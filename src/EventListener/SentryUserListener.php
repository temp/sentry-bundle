<?php

declare(strict_types=1);

namespace Temp\SentryBundle\EventListener;

use ReflectionClass;
use Sentry\State\HubInterface;
use Sentry\State\Scope;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Security;

final class SentryUserListener implements EventSubscriberInterface
{
    private HubInterface $hub;
    private Security $security;

    public function __construct(HubInterface $hub, Security $security)
    {
        $this->hub = $hub;
        $this->security = $security;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $userData['ip_address'] = $event->getRequest()->getClientIp();

        $user = $this->security->getUser();

        if ($user) {
            $userData['type'] = (new ReflectionClass($user))->getShortName();
            $userData['username'] = $user->getUsername();
            $userData['roles'] = $user->getRoles();
        }

        $this->hub->configureScope(
            static function (Scope $scope) use ($userData): void {
                $scope->setUser($userData, true);
            },
        );
    }

    /**
     * @return mixed[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 1],
        ];
    }
}
