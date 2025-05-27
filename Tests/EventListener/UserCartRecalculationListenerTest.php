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

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\CoreBundle\SectionResolver\SectionProviderInterface;
use Sylius\Bundle\ShopBundle\EventListener\UserCartRecalculationListener;
use Sylius\Bundle\ShopBundle\SectionResolver\ShopSection;
use Sylius\Bundle\UserBundle\Event\UserEvent;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Order\Context\CartContextInterface;
use Sylius\Component\Order\Context\CartNotFoundException;
use Sylius\Component\Order\Processor\OrderProcessorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

final class UserCartRecalculationListenerTest extends TestCase
{
    /** @var CartContextInterface|MockObject */
    private MockObject $cartContextMock;

    /** @var OrderProcessorInterface|MockObject */
    private MockObject $orderProcessorMock;

    /** @var SectionProviderInterface|MockObject */
    private MockObject $uriBasedSectionContextMock;

    private UserCartRecalculationListener $userCartRecalculationListener;

    protected function setUp(): void
    {
        $this->cartContextMock = $this->createMock(CartContextInterface::class);
        $this->orderProcessorMock = $this->createMock(OrderProcessorInterface::class);
        $this->uriBasedSectionContextMock = $this->createMock(SectionProviderInterface::class);
        $this->userCartRecalculationListener = new UserCartRecalculationListener($this->cartContextMock, $this->orderProcessorMock, $this->uriBasedSectionContextMock);
    }

    public function testRecalculatesCartForLoggedInUserAndInteractiveLoginEvent(): void
    {
        /** @var Request|MockObject MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        /** @var TokenInterface|MockObject MockObject $tokenMock */
        $tokenMock = $this->createMock(TokenInterface::class);
        /** @var OrderInterface|MockObject MockObject $orderMock */
        $orderMock = $this->createMock(OrderInterface::class);
        /** @var ShopSection|MockObject MockObject $shopSectionMock */
        $shopSectionMock = $this->createMock(ShopSection::class);
        $this->uriBasedSectionContextMock->expects($this->once())->method('getSection')->willReturn($shopSectionMock);
        $this->cartContextMock->expects($this->once())->method('getCart')->willReturn($orderMock);
        $this->orderProcessorMock->expects($this->once())->method('process')->with($orderMock);
        $this->userCartRecalculationListener->recalculateCartWhileLogin(new InteractiveLoginEvent($requestMock, $tokenMock));
    }

    public function testRecalculatesCartForLoggedInUserAndUserEvent(): void
    {
        /** @var UserEvent|MockObject MockObject $eventMock */
        $eventMock = $this->createMock(UserEvent::class);
        /** @var OrderInterface|MockObject MockObject $orderMock */
        $orderMock = $this->createMock(OrderInterface::class);
        /** @var ShopSection|MockObject MockObject $shopSectionMock */
        $shopSectionMock = $this->createMock(ShopSection::class);
        $this->uriBasedSectionContextMock->expects($this->once())->method('getSection')->willReturn($shopSectionMock);
        $this->cartContextMock->expects($this->once())->method('getCart')->willReturn($orderMock);
        $this->orderProcessorMock->expects($this->once())->method('process')->with($orderMock);
        $this->userCartRecalculationListener->recalculateCartWhileLogin($eventMock);
    }

    public function testDoesNothingIfCannotFindCartForInteractiveLoginEvent(): void
    {
        /** @var Request|MockObject MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        /** @var TokenInterface|MockObject MockObject $tokenMock */
        $tokenMock = $this->createMock(TokenInterface::class);
        /** @var ShopSection|MockObject MockObject $shopSectionMock */
        $shopSectionMock = $this->createMock(ShopSection::class);
        $this->uriBasedSectionContextMock->expects($this->once())->method('getSection')->willReturn($shopSectionMock);
        $this->cartContextMock->expects($this->once())->method('getCart')->willThrowException(new CartNotFoundException());
        $this->orderProcessorMock->expects($this->never())->method('process');
        $this->userCartRecalculationListener->recalculateCartWhileLogin(new InteractiveLoginEvent($requestMock, $tokenMock));
    }

    public function testDoesNothingIfCannotFindCartForUserEvent(): void
    {
        /** @var UserEvent|MockObject MockObject $eventMock */
        $eventMock = $this->createMock(UserEvent::class);
        /** @var ShopSection|MockObject MockObject $shopSectionMock */
        $shopSectionMock = $this->createMock(ShopSection::class);
        $this->uriBasedSectionContextMock->expects($this->once())->method('getSection')->willReturn($shopSectionMock);
        $this->cartContextMock->expects($this->once())->method('getCart')->willThrowException(new CartNotFoundException());
        $this->orderProcessorMock->expects($this->never())->method('process');
        $this->userCartRecalculationListener->recalculateCartWhileLogin($eventMock);
    }

    public function testDoesNothingIfSectionIsDifferentThanShopSection(): void
    {
        /** @var UserEvent|MockObject MockObject $eventMock */
        $eventMock = $this->createMock(UserEvent::class);
        $this->uriBasedSectionContextMock->expects($this->once())->method('getSection')->willReturn(null);
        $this->cartContextMock->expects($this->never())->method('getCart');
        $this->orderProcessorMock->expects($this->never())->method('process');
        $this->userCartRecalculationListener->recalculateCartWhileLogin($eventMock);
    }
}
