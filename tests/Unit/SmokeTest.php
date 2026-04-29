<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\UseCase\UseCaseInterface;
use PHPUnit\Framework\TestCase;

final class SmokeTest extends TestCase
{
    public function testAutoloaderResolvesAppNamespace(): void
    {
        self::assertTrue(interface_exists(UseCaseInterface::class));
    }
}
