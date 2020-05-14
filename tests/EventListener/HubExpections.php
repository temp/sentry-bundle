<?php

declare(strict_types=1);

namespace Temp\SentryBundleTests\EventListener;

use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use ReflectionClass;
use Sentry\State\HubInterface;
use Sentry\State\Scope;

trait HubExpections
{
    /**
     * @param ObjectProphecy|HubInterface $hub
     */
    private function expectHubIsNotConfigured(ObjectProphecy $hub): void
    {
        $hub->configureScope(Argument::cetera())
            ->shouldNotBeCalled();
    }

    /**
     * @param ObjectProphecy|HubInterface $hub
     */
    private function expectHubIsConfigured(ObjectProphecy $hub): void
    {
        $hub->configureScope(Argument::cetera())
            ->shouldBeCalled();
    }

    /**
     * @param ObjectProphecy|HubInterface $hub
     * @param mixed[]                     $values
     */
    private function expectHubIsConfiguredWithValues(ObjectProphecy $hub, string $propertyName, array $values): void
    {
        $hub->configureScope(Argument::that(function ($closure) use ($propertyName, $values) {
            $scope = new Scope();
            $closure($scope);
            $rc = new ReflectionClass($scope);
            $rp = $rc->getProperty($propertyName);
            $rp->setAccessible(true);
            $tagsContext = $rp->getValue($scope);

            foreach ($values as $key => $value) {
                $this->assertSame($value, $tagsContext[$key]);
            }

            return true;
        }))
            ->shouldBeCalled();
    }
}
