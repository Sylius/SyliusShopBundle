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
use Sylius\Bundle\CoreBundle\Assigner\IpAssignerInterface;
use Sylius\Bundle\ShopBundle\EventListener\OrderCustomerIpListener;
use Sylius\Component\Core\Model\OrderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Workflow\Event\Event;

final class OrderCustomerIpListenerTest extends TestCase
{
    /** @var IpAssignerInterface|MockObject */
    private MockObject $ipAssignerMock;

    /** @var RequestStack|MockObject */
    private MockObject $requestStackMock;

    private OrderCustomerIpListener $orderCustomerIpListener;

    protected function setUp(): void
    {
        $this->ipAssignerMock = $this->createMock(IpAssignerInterface::class);
        $this->requestStackMock = $this->createMock(RequestStack::class);
        $this->orderCustomerIpListener = new OrderCustomerIpListener($this->ipAssignerMock, $this->requestStackMock);
    }

    public function testUsesAssignerToAssignCustomerIpToOrder(): void
    {
        /** @var Event|MockObject MockObject $eventMock */
        $eventMock = $this->createMock(Event::class);
        /** @var OrderInterface|MockObject MockObject $orderMock */
        $orderMock = $this->createMock(OrderInterface::class);
        /** @var Request|MockObject MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        $eventMock->expects($this->once())->method('getSubject')->willReturn($orderMock);
        $this->requestStackMock->expects($this->once())->method('getMainRequest')->willReturn($requestMock);
        $this->ipAssignerMock->expects($this->once())->method('assign')->with($orderMock, $requestMock);
        ($this->orderCustomerIpListener)($eventMock);
    }

    public function testThrowsExceptionIfEventSubjectIsNotOrder(): void
    {
        /** @var Event|MockObject MockObject $eventMock */
        $eventMock = $this->createMock(Event::class);
        /** @var stdClass|MockObject MockObject $orderMock */
        $orderMock = $this->createMock(stdClass::class);
        $eventMock->expects($this->once())->method('getSubject')->willReturn($orderMock);
        $this->expectException(InvalidArgumentException::class);
        ($this->orderCustomerIpListener)($eventMock);
    }

    public function testDoesNothingIfRequestIsNotAvailable(): void
    {
        /** @var OrderInterface|MockObject MockObject $orderMock */
        $orderMock = $this->createMock(OrderInterface::class);
        /** @var Event|MockObject MockObject $eventMock */
        $eventMock = $this->createMock(Event::class);
        /** @var Request|MockObject MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        $eventMock->expects($this->once())->method('getSubject')->willReturn($orderMock);
        $this->requestStackMock->expects($this->once())->method('getMainRequest')->willReturn(null);
        $this->ipAssignerMock->expects($this->never())->method('assign')->with($orderMock, $requestMock);
        ($this->orderCustomerIpListener)($eventMock);
    }
}
