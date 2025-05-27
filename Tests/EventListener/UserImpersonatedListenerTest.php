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
use Sylius\Bundle\ShopBundle\EventListener\UserImpersonatedListener;
use Sylius\Bundle\UserBundle\Event\UserEvent;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\ShopUserInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\Component\Core\Storage\CartStorageInterface;
use Sylius\Component\User\Model\UserInterface;

final class UserImpersonatedListenerTest extends TestCase
{
    /** @var CartStorageInterface|MockObject */
    private MockObject $cartStorageMock;

    /** @var ChannelContextInterface|MockObject */
    private MockObject $channelContextMock;

    /** @var OrderRepositoryInterface|MockObject */
    private MockObject $orderRepositoryMock;

    private UserImpersonatedListener $userImpersonatedListener;

    protected function setUp(): void
    {
        $this->cartStorageMock = $this->createMock(CartStorageInterface::class);
        $this->channelContextMock = $this->createMock(ChannelContextInterface::class);
        $this->orderRepositoryMock = $this->createMock(OrderRepositoryInterface::class);
        $this->userImpersonatedListener = new UserImpersonatedListener($this->cartStorageMock, $this->channelContextMock, $this->orderRepositoryMock);
    }

    public function testSetsCartIdOfAnImpersonatedCustomerInSession(): void
    {
        /** @var UserEvent|MockObject MockObject $eventMock */
        $eventMock = $this->createMock(UserEvent::class);
        /** @var ShopUserInterface|MockObject MockObject $userMock */
        $userMock = $this->createMock(ShopUserInterface::class);
        /** @var CustomerInterface|MockObject MockObject $customerMock */
        $customerMock = $this->createMock(CustomerInterface::class);
        /** @var ChannelInterface|MockObject MockObject $channelMock */
        $channelMock = $this->createMock(ChannelInterface::class);
        /** @var OrderInterface|MockObject MockObject $cartMock */
        $cartMock = $this->createMock(OrderInterface::class);
        $eventMock->expects($this->once())->method('getUser')->willReturn($userMock);
        $userMock->expects($this->once())->method('getCustomer')->willReturn($customerMock);
        $this->channelContextMock->expects($this->once())->method('getChannel')->willReturn($channelMock);
        $this->orderRepositoryMock->expects($this->once())->method('findLatestCartByChannelAndCustomer')->with($channelMock, $customerMock)->willReturn($cartMock);
        $this->cartStorageMock->expects($this->once())->method('setForChannel')->with($channelMock, $cartMock);
        $this->userImpersonatedListener->onUserImpersonated($eventMock);
    }

    public function testRemovesTheCurrentCartIdIfAnImpersonatedCustomerHasNoCart(): void
    {
        /** @var UserEvent|MockObject MockObject $eventMock */
        $eventMock = $this->createMock(UserEvent::class);
        /** @var ShopUserInterface|MockObject MockObject $userMock */
        $userMock = $this->createMock(ShopUserInterface::class);
        /** @var CustomerInterface|MockObject MockObject $customerMock */
        $customerMock = $this->createMock(CustomerInterface::class);
        /** @var ChannelInterface|MockObject MockObject $channelMock */
        $channelMock = $this->createMock(ChannelInterface::class);
        $eventMock->expects($this->once())->method('getUser')->willReturn($userMock);
        $userMock->expects($this->once())->method('getCustomer')->willReturn($customerMock);
        $this->channelContextMock->expects($this->once())->method('getChannel')->willReturn($channelMock);
        $this->orderRepositoryMock->expects($this->once())->method('findLatestCartByChannelAndCustomer')->with($channelMock, $customerMock)->willReturn(null);
        $this->cartStorageMock->expects($this->once())->method('removeForChannel')->with($channelMock);
        $this->userImpersonatedListener->onUserImpersonated($eventMock);
    }

    public function testDoesNothingWhenTheUserIsNotAShopUserType(): void
    {
        /** @var UserEvent|MockObject MockObject $eventMock */
        $eventMock = $this->createMock(UserEvent::class);
        /** @var UserInterface|MockObject MockObject $userMock */
        $userMock = $this->createMock(UserInterface::class);
        $eventMock->expects($this->once())->method('getUser')->willReturn($userMock);
        $this->userImpersonatedListener->onUserImpersonated($eventMock);
    }
}
