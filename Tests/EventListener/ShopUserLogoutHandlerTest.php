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
use Sylius\Bundle\ShopBundle\EventListener\ShopUserLogoutHandler;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Storage\CartStorageInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;

final class ShopUserLogoutHandlerTest extends TestCase
{
    /** @var ChannelContextInterface|MockObject */
    private MockObject $channelContextMock;

    /** @var CartStorageInterface|MockObject */
    private MockObject $cartStorageMock;

    private ShopUserLogoutHandler $shopUserLogoutHandler;

    protected function setUp(): void
    {
        $this->channelContextMock = $this->createMock(ChannelContextInterface::class);
        $this->cartStorageMock = $this->createMock(CartStorageInterface::class);
        $this->shopUserLogoutHandler = new ShopUserLogoutHandler($this->channelContextMock, $this->cartStorageMock);
    }

    public function testClearsCartSessionAfterLoggingOut(): void
    {
        /** @var ChannelInterface|MockObject MockObject $channelMock */
        $channelMock = $this->createMock(ChannelInterface::class);
        /** @var LogoutEvent|MockObject MockObject $logoutEventMock */
        $logoutEventMock = $this->createMock(LogoutEvent::class);
        $this->channelContextMock->expects($this->once())->method('getChannel')->willReturn($channelMock);
        $this->cartStorageMock->expects($this->once())->method('removeForChannel')->with($channelMock);
        $this->shopUserLogoutHandler->onLogout($logoutEventMock);
    }
}
