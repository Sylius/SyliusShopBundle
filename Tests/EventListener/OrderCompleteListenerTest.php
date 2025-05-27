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
use Sylius\Bundle\CoreBundle\Mailer\OrderEmailManagerInterface;
use Sylius\Bundle\ShopBundle\EventListener\OrderCompleteListener;
use Sylius\Component\Core\Model\OrderInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

final class OrderCompleteListenerTest extends TestCase
{
    /** @var OrderEmailManagerInterface|MockObject */
    private MockObject $orderEmailManagerMock;

    private OrderCompleteListener $orderCompleteListener;

    protected function setUp(): void
    {
        $this->orderEmailManagerMock = $this->createMock(OrderEmailManagerInterface::class);
        $this->orderCompleteListener = new OrderCompleteListener($this->orderEmailManagerMock);
    }

    public function testSendsAConfirmationEmail(): void
    {
        /** @var GenericEvent|MockObject MockObject $eventMock */
        $eventMock = $this->createMock(GenericEvent::class);
        /** @var OrderInterface|MockObject MockObject $orderMock */
        $orderMock = $this->createMock(OrderInterface::class);
        $eventMock->expects($this->once())->method('getSubject')->willReturn($orderMock);
        $this->orderEmailManagerMock->expects($this->once())->method('sendConfirmationEmail')->with($orderMock);
        $this->orderCompleteListener->sendConfirmationEmail($eventMock);
    }

    public function testThrowsAnInvalidArgumentExceptionIfAnEventSubjectIsNotAnOrderInstance(): void
    {
        /** @var GenericEvent|MockObject MockObject $eventMock */
        $eventMock = $this->createMock(GenericEvent::class);
        /** @var stdClass|MockObject MockObject $orderMock */
        $orderMock = $this->createMock(stdClass::class);
        $eventMock->expects($this->once())->method('getSubject')->willReturn($orderMock);
        $this->expectException(InvalidArgumentException::class);
        $this->orderCompleteListener->sendConfirmationEmail($eventMock);
    }
}
