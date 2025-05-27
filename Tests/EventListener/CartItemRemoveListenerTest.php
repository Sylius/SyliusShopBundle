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
use Sylius\Bundle\ShopBundle\EventListener\CartItemRemoveListener;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Order\Modifier\OrderModifierInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

final class CartItemRemoveListenerTest extends TestCase
{
    /** @var OrderModifierInterface|MockObject */
    private MockObject $orderModifierMock;

    private CartItemRemoveListener $cartItemRemoveListener;

    protected function setUp(): void
    {
        $this->orderModifierMock = $this->createMock(OrderModifierInterface::class);
        $this->cartItemRemoveListener = new CartItemRemoveListener($this->orderModifierMock);
    }

    public function testRemovesOrderItemFromOrder(): void
    {
        /** @var GenericEvent|MockObject MockObject $eventMock */
        $eventMock = $this->createMock(GenericEvent::class);
        /** @var OrderItemInterface|MockObject MockObject $orderItemMock */
        $orderItemMock = $this->createMock(OrderItemInterface::class);
        /** @var OrderInterface|MockObject MockObject $orderMock */
        $orderMock = $this->createMock(OrderInterface::class);
        $orderItemMock->expects($this->once())->method('getOrder')->willReturn($orderMock);
        $eventMock->expects($this->once())->method('getSubject')->willReturn($orderItemMock);
        $this->orderModifierMock->expects($this->once())->method('removeFromOrder')->with($orderMock, $orderItemMock);
        $this->cartItemRemoveListener->removeFromOrder($eventMock);
    }

    public function testThrowsExceptionIfEventSubjectIsNotOrderItem(): void
    {
        /** @var GenericEvent|MockObject MockObject $eventMock */
        $eventMock = $this->createMock(GenericEvent::class);
        $eventMock->expects($this->once())->method('getSubject')->willReturn(new stdClass());
        $this->expectException(InvalidArgumentException::class);
        $this->cartItemRemoveListener->removeFromOrder($eventMock);
    }
}
