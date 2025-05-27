<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Sylius Sp. z o.o.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\Sylius\Bundle\ShopBundle\EventListener;

use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;
use Sylius\Bundle\ShopBundle\EventListener\OrderLocaleAssigner;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Sylius\Resource\Symfony\EventDispatcher\GenericEvent;

final class OrderLocaleAssignerTest extends TestCase
{
    /** @var LocaleContextInterface|MockObject */
    private MockObject $localeContextMock;

    private OrderLocaleAssigner $orderLocaleAssigner;

    protected function setUp(): void
    {
        $this->localeContextMock = $this->createMock(LocaleContextInterface::class);
        $this->orderLocaleAssigner = new OrderLocaleAssigner($this->localeContextMock);
    }

    public function testAssignsLocaleToAnOrder(): void
    {
        /** @var OrderInterface|MockObject MockObject $orderMock */
        $orderMock = $this->createMock(OrderInterface::class);
        /** @var GenericEvent|MockObject MockObject $eventMock */
        $eventMock = $this->createMock(GenericEvent::class);
        $eventMock->expects($this->once())->method('getSubject')->willReturn($orderMock);
        $this->localeContextMock->expects($this->once())->method('getLocaleCode')->willReturn('pl_PL');
        $orderMock->expects($this->once())->method('setLocaleCode')->with('pl_PL');
        $this->orderLocaleAssigner->assignLocale($eventMock);
    }

    public function testThrowsInvalidArgumentExceptionIfSubjectItNotOrder(): void
    {
        /** @var GenericEvent|MockObject MockObject $eventMock */
        $eventMock = $this->createMock(GenericEvent::class);
        $eventMock->expects($this->once())->method('getSubject')->willReturn(new stdClass());
        $this->expectException(InvalidArgumentException::class);
        $this->orderLocaleAssigner->assignLocale($eventMock);
    }
}
