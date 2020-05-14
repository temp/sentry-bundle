<?php

declare (strict_types=1);

namespace Temp\SentryBundleTests;

use PHPUnit\Framework\TestCase;
use Temp\SentryBundle\TempSentryBundle;

/**
 * @covers \Temp\SentryBundle\TempSentryBundle
 */
final class TempSentryBundleTest extends TestCase
{
    public function testBundle(): void
    {
        $bundle = new TempSentryBundle();

        $this->assertSame('TempSentryBundle', $bundle->getName());
    }
}
