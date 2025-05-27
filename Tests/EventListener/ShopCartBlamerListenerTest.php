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
use Sylius\Bundle\CoreBundle\SectionResolver\SectionInterface;
use Sylius\Bundle\CoreBundle\SectionResolver\SectionProviderInterface;
use Sylius\Bundle\ShopBundle\EventListener\ShopCartBlamerListener;
use Sylius\Bundle\ShopBundle\SectionResolver\ShopSection;
use Sylius\Bundle\UserBundle\Event\UserEvent;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\ShopUserInterface;
use Sylius\Component\Order\Context\CartContextInterface;
use Sylius\Component\Order\Context\CartNotFoundException;
use Sylius\Resource\Exception\UnexpectedTypeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

final class ShopCartBlamerListenerTest extends TestCase
{
    /** @var CartContextInterface|MockObject */
    private MockObject $cartContextMock;

    /** @var SectionProviderInterface|MockObject */
    private MockObject $sectionResolverMock;

    private ShopCartBlamerListener $shopCartBlamerListener;

    protected function setUp(): void
    {
        $this->cartContextMock = $this->createMock(CartContextInterface::class);
        $this->sectionResolverMock = $this->createMock(SectionProviderInterface::class);
        $this->shopCartBlamerListener = new ShopCartBlamerListener($this->cartContextMock, $this->sectionResolverMock);
    }

    public function testThrowsAnExceptionWhenCartDoesNotImplementCoreOrderInterfaceOnImplicitLogin(): void
    {
        /** @var \Sylius\Component\Order\Model\OrderInterface|MockObject MockObject $orderMock */
        $orderMock = $this->createMock(\Sylius\Component\Order\Model\OrderInterface::class);
        /** @var ShopUserInterface|MockObject MockObject $userMock */
        $userMock = $this->createMock(ShopUserInterface::class);
        /** @var UserEvent|MockObject MockObject $userEventMock */
        $userEventMock = $this->createMock(UserEvent::class);
        /** @var ShopSection|MockObject MockObject $shopSectionMock */
        $shopSectionMock = $this->createMock(ShopSection::class);
        $this->sectionResolverMock->expects($this->once())->method('getSection')->willReturn($shopSectionMock);
        $this->cartContextMock->expects($this->once())->method('getCart')->willReturn($orderMock);
        $userEventMock->expects($this->once())->method('getUser')->willReturn($userMock);
        $this->expectException(UnexpectedTypeException::class);
        $this->shopCartBlamerListener->onImplicitLogin($userEventMock);
    }

    public function testThrowsAnExceptionWhenCartDoesNotImplementCoreOrderInterfaceOnInteractiveLogin(): void
    {
        /** @var \Sylius\Component\Order\Model\OrderInterface|MockObject MockObject $orderMock */
        $orderMock = $this->createMock(\Sylius\Component\Order\Model\OrderInterface::class);
        /** @var ShopUserInterface|MockObject MockObject $userMock */
        $userMock = $this->createMock(ShopUserInterface::class);
        /** @var Request|MockObject MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        /** @var TokenInterface|MockObject MockObject $tokenMock */
        $tokenMock = $this->createMock(TokenInterface::class);
        /** @var ShopSection|MockObject MockObject $shopSectionMock */
        $shopSectionMock = $this->createMock(ShopSection::class);
        $this->sectionResolverMock->expects($this->once())->method('getSection')->willReturn($shopSectionMock);
        $this->cartContextMock->expects($this->once())->method('getCart')->willReturn($orderMock);
        $tokenMock->expects($this->once())->method('getUser')->willReturn($userMock);
        $this->expectException(UnexpectedTypeException::class);
        $this->shopCartBlamerListener->onInteractiveLogin(new InteractiveLoginEvent($requestMock, $tokenMock));
    }

    public function testBlamesCartOnUserOnImplicitLogin(): void
    {
        /** @var OrderInterface|MockObject MockObject $cartMock */
        $cartMock = $this->createMock(OrderInterface::class);
        /** @var UserEvent|MockObject MockObject $userEventMock */
        $userEventMock = $this->createMock(UserEvent::class);
        /** @var ShopUserInterface|MockObject MockObject $userMock */
        $userMock = $this->createMock(ShopUserInterface::class);
        /** @var CustomerInterface|MockObject MockObject $customerMock */
        $customerMock = $this->createMock(CustomerInterface::class);
        /** @var ShopSection|MockObject MockObject $shopSectionMock */
        $shopSectionMock = $this->createMock(ShopSection::class);
        $this->sectionResolverMock->expects($this->once())->method('getSection')->willReturn($shopSectionMock);
        $this->cartContextMock->expects($this->once())->method('getCart')->willReturn($cartMock);
        $cartMock->expects($this->once())->method('getCustomer')->willReturn(null);
        $userEventMock->expects($this->once())->method('getUser')->willReturn($userMock);
        $userMock->expects($this->once())->method('getCustomer')->willReturn($customerMock);
        $cartMock->expects($this->once())->method('setCustomerWithAuthorization')->with($customerMock);
        $this->shopCartBlamerListener->onImplicitLogin($userEventMock);
    }

    public function testBlamesCartOnUserOnInteractiveLogin(): void
    {
        /** @var OrderInterface|MockObject MockObject $cartMock */
        $cartMock = $this->createMock(OrderInterface::class);
        /** @var Request|MockObject MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        /** @var TokenInterface|MockObject MockObject $tokenMock */
        $tokenMock = $this->createMock(TokenInterface::class);
        /** @var ShopUserInterface|MockObject MockObject $userMock */
        $userMock = $this->createMock(ShopUserInterface::class);
        /** @var CustomerInterface|MockObject MockObject $customerMock */
        $customerMock = $this->createMock(CustomerInterface::class);
        /** @var ShopSection|MockObject MockObject $shopSectionMock */
        $shopSectionMock = $this->createMock(ShopSection::class);
        $this->sectionResolverMock->expects($this->once())->method('getSection')->willReturn($shopSectionMock);
        $this->cartContextMock->expects($this->once())->method('getCart')->willReturn($cartMock);
        $cartMock->expects($this->once())->method('getCustomer')->willReturn(null);
        $tokenMock->expects($this->once())->method('getUser')->willReturn($userMock);
        $userMock->expects($this->once())->method('getCustomer')->willReturn($customerMock);
        $cartMock->expects($this->once())->method('setCustomerWithAuthorization')->with($customerMock);
        $this->shopCartBlamerListener->onInteractiveLogin(new InteractiveLoginEvent($requestMock, $tokenMock));
    }

    public function testDoesNothingIfGivenCartHasBeenBlamedInPast(): void
    {
        /** @var OrderInterface|MockObject MockObject $cartMock */
        $cartMock = $this->createMock(OrderInterface::class);
        /** @var Request|MockObject MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        /** @var TokenInterface|MockObject MockObject $tokenMock */
        $tokenMock = $this->createMock(TokenInterface::class);
        /** @var ShopUserInterface|MockObject MockObject $userMock */
        $userMock = $this->createMock(ShopUserInterface::class);
        /** @var CustomerInterface|MockObject MockObject $customerMock */
        $customerMock = $this->createMock(CustomerInterface::class);
        /** @var ShopSection|MockObject MockObject $shopSectionMock */
        $shopSectionMock = $this->createMock(ShopSection::class);
        $this->sectionResolverMock->expects($this->once())->method('getSection')->willReturn($shopSectionMock);
        $this->cartContextMock->expects($this->once())->method('getCart')->willReturn($cartMock);
        $cartMock->expects($this->once())->method('getCustomer')->willReturn($customerMock);
        $tokenMock->expects($this->once())->method('getUser')->willReturn($userMock);
        $cartMock->expects($this->never())->method('setCustomerWithAuthorization');
        $this->shopCartBlamerListener->onInteractiveLogin(new InteractiveLoginEvent($requestMock, $tokenMock));
    }

    public function testDoesNothingIfGivenUserIsInvalidOnInteractiveLogin(): void
    {
        /** @var OrderInterface|MockObject MockObject $cartMock */
        $cartMock = $this->createMock(OrderInterface::class);
        /** @var Request|MockObject MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        /** @var TokenInterface|MockObject MockObject $tokenMock */
        $tokenMock = $this->createMock(TokenInterface::class);
        /** @var ShopSection|MockObject MockObject $shopSectionMock */
        $shopSectionMock = $this->createMock(ShopSection::class);
        $this->sectionResolverMock->expects($this->once())->method('getSection')->willReturn($shopSectionMock);
        $this->cartContextMock->expects($this->never())->method('getCart');
        $tokenMock->expects($this->once())->method('getUser')->willReturn(null);
        $cartMock->expects($this->never())->method('setCustomerWithAuthorization');
        $this->shopCartBlamerListener->onInteractiveLogin(new InteractiveLoginEvent($requestMock, $tokenMock));
    }

    public function testDoesNothingIfThereIsNoExistingCartOnImplicitLogin(): void
    {
        /** @var UserEvent|MockObject MockObject $userEventMock */
        $userEventMock = $this->createMock(UserEvent::class);
        /** @var ShopUserInterface|MockObject MockObject $userMock */
        $userMock = $this->createMock(ShopUserInterface::class);
        /** @var ShopSection|MockObject MockObject $shopSectionMock */
        $shopSectionMock = $this->createMock(ShopSection::class);
        $this->sectionResolverMock->expects($this->once())->method('getSection')->willReturn($shopSectionMock);
        $this->cartContextMock->expects($this->once())->method('getCart')->willThrowException(new CartNotFoundException());
        $userEventMock->expects($this->once())->method('getUser')->willReturn($userMock);
        $this->shopCartBlamerListener->onImplicitLogin($userEventMock);
    }

    public function testDoesNothingIfThereIsNoExistingCartOnInteractiveLogin(): void
    {
        /** @var Request|MockObject MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        /** @var TokenInterface|MockObject MockObject $tokenMock */
        $tokenMock = $this->createMock(TokenInterface::class);
        /** @var ShopUserInterface|MockObject MockObject $userMock */
        $userMock = $this->createMock(ShopUserInterface::class);
        /** @var ShopSection|MockObject MockObject $shopSectionMock */
        $shopSectionMock = $this->createMock(ShopSection::class);
        $this->sectionResolverMock->expects($this->once())->method('getSection')->willReturn($shopSectionMock);
        $this->cartContextMock->expects($this->once())->method('getCart')->willThrowException(new CartNotFoundException());
        $tokenMock->expects($this->once())->method('getUser')->willReturn($userMock);
        $this->shopCartBlamerListener->onInteractiveLogin(new InteractiveLoginEvent($requestMock, $tokenMock));
    }

    public function testDoesNothingIfTheCurrentSectionIsNotShopOnImplicitLogin(): void
    {
        /** @var UserEvent|MockObject MockObject $userEventMock */
        $userEventMock = $this->createMock(UserEvent::class);
        /** @var SectionInterface|MockObject MockObject $sectionMock */
        $sectionMock = $this->createMock(SectionInterface::class);
        $this->sectionResolverMock->expects($this->once())->method('getSection')->willReturn($sectionMock);
        $userEventMock->expects($this->never())->method('getUser');
        $this->cartContextMock->expects($this->never())->method('getCart');
        $this->shopCartBlamerListener->onImplicitLogin($userEventMock);
    }

    public function testDoesNothingIfTheCurrentSectionIsNotShopOnInteractiveLogin(): void
    {
        /** @var Request|MockObject MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        /** @var TokenInterface|MockObject MockObject $tokenMock */
        $tokenMock = $this->createMock(TokenInterface::class);
        /** @var SectionInterface|MockObject MockObject $sectionMock */
        $sectionMock = $this->createMock(SectionInterface::class);
        $this->sectionResolverMock->expects($this->once())->method('getSection')->willReturn($sectionMock);
        $tokenMock->expects($this->never())->method('getUser');
        $this->cartContextMock->expects($this->never())->method('getCart');
        $this->shopCartBlamerListener->onInteractiveLogin(new InteractiveLoginEvent($requestMock, $tokenMock));
    }
}
