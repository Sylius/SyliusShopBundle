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
use Sylius\Bundle\OrderBundle\Controller\AddToCartCommandInterface;
use Sylius\Bundle\ShopBundle\EventListener\CartItemAddListener;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Order\Modifier\OrderModifierInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

final class CartItemAddListenerTest extends TestCase
{
    /** @var OrderModifierInterface|MockObject */
    private MockObject $orderModifierMock;

    private CartItemAddListener $cartItemAddListener;

    protected function setUp(): void
    {
        $this->orderModifierMock = $this->createMock(OrderModifierInterface::class);
        $this->cartItemAddListener = new CartItemAddListener($this->orderModifierMock);
    }

    public function testAddsCartItemToOrder(): void
    {
        /** @var GenericEvent|MockObject MockObject $eventMock */
        $eventMock = $this->createMock(GenericEvent::class);
        /** @var AddToCartCommandInterface|MockObject MockObject $addToCartCommandMock */
        $addToCartCommandMock = $this->createMock(AddToCartCommandInterface::class);
        /** @var OrderItemInterface|MockObject MockObject $orderItemMock */
        $orderItemMock = $this->createMock(OrderItemInterface::class);
        /** @var OrderInterface|MockObject MockObject $orderMock */
        $orderMock = $this->createMock(OrderInterface::class);
        $addToCartCommandMock->expects($this->once())->method('getCart')->willReturn($orderMock);
        $addToCartCommandMock->expects($this->once())->method('getCartItem')->willReturn($orderItemMock);
        $eventMock->expects($this->once())->method('getSubject')->willReturn($addToCartCommandMock);

        $this->orderModifierMock->expects($this->once())->method('addToOrder')->with($orderMock, $orderItemMock);

        $this->cartItemAddListener->addToOrder($eventMock);
    }

    public function testThrowsExceptionIfEventSubjectIsNotAddToCartCommand(): void
    {
        /** @var GenericEvent|MockObject MockObject $eventMock */
        $eventMock = $this->createMock(GenericEvent::class);
        $eventMock->expects($this->once())->method('getSubject')->willReturn(new stdClass());
        $this->expectException(InvalidArgumentException::class);
        $this->cartItemAddListener->addToOrder($eventMock);
    }
}
