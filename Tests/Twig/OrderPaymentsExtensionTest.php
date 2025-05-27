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

namespace Tests\Sylius\Bundle\ShopBundle\Twig;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\ShopBundle\Twig\OrderPaymentsExtension;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Payment\Resolver\PaymentMethodsResolverInterface;
use Twig\Extension\AbstractExtension;

final class OrderPaymentsExtensionTest extends TestCase
{
    /** @var PaymentMethodsResolverInterface|MockObject */
    private MockObject $paymentMethodsResolverMock;

    private OrderPaymentsExtension $orderPaymentsExtension;

    protected function setUp(): void
    {
        $this->paymentMethodsResolverMock = $this->createMock(PaymentMethodsResolverInterface::class);
        $this->orderPaymentsExtension = new OrderPaymentsExtension($this->paymentMethodsResolverMock);
    }

    public function testATwigExtension(): void
    {
        $this->assertInstanceOf(AbstractExtension::class, $this->orderPaymentsExtension);
    }

    public function testReturnsFalseIfOrderHasNoNewPayments(): void
    {
        /** @var OrderInterface|MockObject MockObject $orderMock */
        $orderMock = $this->createMock(OrderInterface::class);
        $orderMock->expects($this->once())->method('getPayments')->willReturn(new ArrayCollection());
        $this->assertFalse($this->orderPaymentsExtension->allNewPaymentsCanBePaid($orderMock));
    }

    public function testReturnsFalseWhenAllNewPaymentsHaveNoSupportedMethods(): void
    {
        /** @var PaymentInterface|MockObject MockObject $firstPaymentMock */
        $firstPaymentMock = $this->createMock(PaymentInterface::class);
        /** @var PaymentInterface|MockObject MockObject $secondPaymentMock */
        $secondPaymentMock = $this->createMock(PaymentInterface::class);
        /** @var PaymentInterface|MockObject MockObject $thirdPaymentMock */
        $thirdPaymentMock = $this->createMock(PaymentInterface::class);
        /** @var OrderInterface|MockObject MockObject $orderMock */
        $orderMock = $this->createMock(OrderInterface::class);
        $firstPaymentMock->expects($this->once())->method('getState')->willReturn(PaymentInterface::STATE_NEW);
        $secondPaymentMock->expects($this->once())->method('getState')->willReturn(PaymentInterface::STATE_CANCELLED);
        $thirdPaymentMock->expects($this->once())->method('getState')->willReturn(PaymentInterface::STATE_NEW);
        $orderMock->expects($this->once())->method('getPayments')->willReturn(new ArrayCollection([
            $firstPaymentMock,
            $secondPaymentMock,
            $thirdPaymentMock,
        ]));

        $this->paymentMethodsResolverMock
            ->method('getSupportedMethods')
            ->willReturnMap([
                [$firstPaymentMock, []],
                [$thirdPaymentMock, []],
            ])
        ;

        $this->assertFalse($this->orderPaymentsExtension->allNewPaymentsCanBePaid($orderMock));
    }

    public function testReturnsFalseWhenAtLeastOneNewPaymentHasNoSupportedMethods(): void
    {
        /** @var PaymentInterface|MockObject MockObject $firstPaymentMock */
        $firstPaymentMock = $this->createMock(PaymentInterface::class);
        /** @var PaymentInterface|MockObject MockObject $secondPaymentMock */
        $secondPaymentMock = $this->createMock(PaymentInterface::class);
        /** @var PaymentInterface|MockObject MockObject $thirdPaymentMock */
        $thirdPaymentMock = $this->createMock(PaymentInterface::class);
        /** @var OrderInterface|MockObject MockObject $orderMock */
        $orderMock = $this->createMock(OrderInterface::class);
        $firstPaymentMock->expects($this->once())->method('getState')->willReturn(PaymentInterface::STATE_NEW);
        $secondPaymentMock->expects($this->once())->method('getState')->willReturn(PaymentInterface::STATE_CANCELLED);
        $thirdPaymentMock->expects($this->once())->method('getState')->willReturn(PaymentInterface::STATE_NEW);
        $orderMock->expects($this->once())->method('getPayments')->willReturn(new ArrayCollection([
            $firstPaymentMock,
            $secondPaymentMock,
            $thirdPaymentMock,
        ]));

        $this->paymentMethodsResolverMock
            ->method('getSupportedMethods')
            ->willReturnMap([
                [$firstPaymentMock, ['method']],
                [$thirdPaymentMock, []],
            ])
        ;

        $this->assertFalse($this->orderPaymentsExtension->allNewPaymentsCanBePaid($orderMock));
    }

    public function testReturnsTrueWhenAllNewPaymentsHaveAtLeastOneSupportedMethod(): void
    {
        /** @var PaymentInterface|MockObject MockObject $firstPaymentMock */
        $firstPaymentMock = $this->createMock(PaymentInterface::class);
        /** @var PaymentInterface|MockObject MockObject $secondPaymentMock */
        $secondPaymentMock = $this->createMock(PaymentInterface::class);
        /** @var PaymentInterface|MockObject MockObject $thirdPaymentMock */
        $thirdPaymentMock = $this->createMock(PaymentInterface::class);
        /** @var OrderInterface|MockObject MockObject $orderMock */
        $orderMock = $this->createMock(OrderInterface::class);
        $firstPaymentMock->expects($this->once())->method('getState')->willReturn(PaymentInterface::STATE_NEW);
        $secondPaymentMock->expects($this->once())->method('getState')->willReturn(PaymentInterface::STATE_CANCELLED);
        $thirdPaymentMock->expects($this->once())->method('getState')->willReturn(PaymentInterface::STATE_NEW);
        $orderMock->expects($this->once())->method('getPayments')->willReturn(new ArrayCollection([
            $firstPaymentMock,
            $secondPaymentMock,
            $thirdPaymentMock,
        ]));

        $this->paymentMethodsResolverMock
            ->method('getSupportedMethods')
            ->willReturnMap([
                [$firstPaymentMock, ['method', 'another_method']],
                [$thirdPaymentMock, ['method']],
            ])
        ;
        $this->assertTrue($this->orderPaymentsExtension->allNewPaymentsCanBePaid($orderMock));
    }
}
