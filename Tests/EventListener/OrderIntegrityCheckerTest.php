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

use Doctrine\Persistence\ObjectManager;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;
use Sylius\Bundle\CoreBundle\Order\Checker\OrderPromotionsIntegrityCheckerInterface;
use Sylius\Bundle\ShopBundle\EventListener\OrderIntegrityChecker;
use Sylius\Bundle\ShopBundle\EventListener\OrderIntegrityCheckerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PromotionInterface;
use Sylius\Resource\Symfony\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;

final class OrderIntegrityCheckerTest extends TestCase
{
    /** @var RouterInterface|MockObject */
    private MockObject $routerMock;

    /** @var ObjectManager|MockObject */
    private MockObject $orderManagerMock;

    /** @var OrderPromotionsIntegrityCheckerInterface|MockObject */
    private MockObject $orderPromotionsIntegrityCheckerMock;

    private OrderIntegrityChecker $orderIntegrityChecker;

    protected function setUp(): void
    {
        $this->routerMock = $this->createMock(RouterInterface::class);
        $this->orderManagerMock = $this->createMock(ObjectManager::class);
        $this->orderPromotionsIntegrityCheckerMock = $this->createMock(OrderPromotionsIntegrityCheckerInterface::class);
        $this->orderIntegrityChecker = new OrderIntegrityChecker($this->routerMock, $this->orderManagerMock, $this->orderPromotionsIntegrityCheckerMock);
    }

    public function testImplementsOrderIntegrityCheckerInterface(): void
    {
        $this->assertInstanceOf(OrderIntegrityCheckerInterface::class, $this->orderIntegrityChecker);
    }

    public function testDoesNothingIfGivenOrderHasValidPromotionApplied(): void
    {
        /** @var OrderInterface|MockObject MockObject $orderMock */
        $orderMock = $this->createMock(OrderInterface::class);
        /** @var PromotionInterface|MockObject MockObject $promotionMock */
        $promotionMock = $this->createMock(PromotionInterface::class);
        /** @var GenericEvent|MockObject MockObject $eventMock */
        $eventMock = $this->createMock(GenericEvent::class);
        $eventMock->expects($this->once())->method('getSubject')->willReturn($orderMock);
        $orderMock->expects($this->exactly(2))->method('getTotal')->willReturnOnConsecutiveCalls(1000, 1000);
        $this->orderPromotionsIntegrityCheckerMock->expects($this->once())->method('check')->with($orderMock)->willReturn(null);
        $eventMock->expects($this->never())->method('stop');
        $eventMock->expects($this->never())->method('setResponse');
        $this->orderIntegrityChecker->check($eventMock);
    }

    public function testStopsFutureActionIfGivenOrderHasDifferentPromotionApplied(): void
    {
        /** @var OrderInterface|MockObject MockObject $orderMock */
        $orderMock = $this->createMock(OrderInterface::class);
        /** @var PromotionInterface|MockObject MockObject $oldPromotionMock */
        $oldPromotionMock = $this->createMock(PromotionInterface::class);
        /** @var PromotionInterface|MockObject MockObject $newPromotionMock */
        $newPromotionMock = $this->createMock(PromotionInterface::class);
        /** @var GenericEvent|MockObject MockObject $eventMock */
        $eventMock = $this->createMock(GenericEvent::class);
        $eventMock->expects($this->once())->method('getSubject')->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getTotal')->willReturn(1000);
        $oldPromotionMock->expects($this->once())->method('getName')->willReturn('Christmas');
        $this->routerMock->expects($this->once())->method('generate')->with('sylius_shop_checkout_complete')->willReturn('checkout.com');
        $this->orderPromotionsIntegrityCheckerMock->expects($this->once())->method('check')->with($orderMock)->willReturn($oldPromotionMock);
        $eventMock->expects($this->once())->method('stop')->with('sylius.order.promotion_integrity', GenericEvent::TYPE_ERROR, ['%promotionName%' => 'Christmas']);
        $eventMock->expects($this->once())->method('setResponse')->with(new RedirectResponse('checkout.com'));
        $this->orderManagerMock->expects($this->once())->method('persist')->with($orderMock);
        $this->orderManagerMock->expects($this->once())->method('flush');
        $this->orderIntegrityChecker->check($eventMock);
    }

    public function testStopsFutureActionIfGivenOrderHasDifferentTotalValue(): void
    {
        /** @var OrderInterface|MockObject MockObject $orderMock */
        $orderMock = $this->createMock(OrderInterface::class);
        /** @var PromotionInterface|MockObject MockObject $promotionMock */
        $promotionMock = $this->createMock(PromotionInterface::class);
        /** @var GenericEvent|MockObject MockObject $eventMock */
        $eventMock = $this->createMock(GenericEvent::class);
        $eventMock->expects($this->once())->method('getSubject')->willReturn($orderMock);
        $orderMock->expects($this->exactly(2))->method('getTotal')->willReturnOnConsecutiveCalls(1000, 1500);
        $this->routerMock->expects($this->once())->method('generate')->with('sylius_shop_checkout_complete')->willReturn('checkout.com');
        $this->orderPromotionsIntegrityCheckerMock->expects($this->once())->method('check')->with($orderMock)->willReturn(null);
        $eventMock->expects($this->once())->method('stop')->with('sylius.order.total_integrity', GenericEvent::TYPE_ERROR);
        $eventMock->expects($this->once())->method('setResponse')->with(new RedirectResponse('checkout.com'));
        $this->orderManagerMock->expects($this->once())->method('persist')->with($orderMock);
        $this->orderManagerMock->expects($this->once())->method('flush');
        $this->orderIntegrityChecker->check($eventMock);
    }

    public function testStopsFutureActionIfGivenOrderHasNoPromotionApplied(): void
    {
        /** @var OrderInterface|MockObject MockObject $orderMock */
        $orderMock = $this->createMock(OrderInterface::class);
        /** @var PromotionInterface|MockObject MockObject $promotionMock */
        $promotionMock = $this->createMock(PromotionInterface::class);
        /** @var GenericEvent|MockObject MockObject $eventMock */
        $eventMock = $this->createMock(GenericEvent::class);
        $eventMock->expects($this->once())->method('getSubject')->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getTotal')->willReturn(1000);
        $promotionMock->expects($this->once())->method('getName')->willReturn('Christmas');
        $this->orderPromotionsIntegrityCheckerMock->expects($this->once())->method('check')->with($orderMock)->willReturn($promotionMock);
        $this->routerMock->expects($this->once())->method('generate')->with('sylius_shop_checkout_complete')->willReturn('checkout.com');
        $eventMock->expects($this->once())->method('stop')->with('sylius.order.promotion_integrity', GenericEvent::TYPE_ERROR, ['%promotionName%' => 'Christmas']);
        $eventMock->expects($this->once())->method('setResponse')->with(new RedirectResponse('checkout.com'));
        $this->orderManagerMock->expects($this->once())->method('persist')->with($orderMock);
        $this->orderManagerMock->expects($this->once())->method('flush');
        $this->orderIntegrityChecker->check($eventMock);
    }

    public function testThrowsInvalidArgumentExceptionIfEventSubjectIsNotOrder(): void
    {
        /** @var GenericEvent|MockObject MockObject $eventMock */
        $eventMock = $this->createMock(GenericEvent::class);
        $eventMock->expects($this->once())->method('getSubject')->willReturn(new stdClass());
        $this->expectException(InvalidArgumentException::class);
        $this->orderIntegrityChecker->check($eventMock);
    }
}
