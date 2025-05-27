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
use Sylius\Bundle\ShopBundle\EventListener\ShopCustomerAccountSubSectionCacheControlSubscriber;
use Sylius\Bundle\ShopBundle\SectionResolver\ShopCustomerAccountSubSection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\KernelInterface;

final class ShopCustomerAccountSubSectionCacheControlSubscriberTest extends TestCase
{
    /** @var SectionProviderInterface|MockObject */
    private MockObject $sectionProviderMock;

    private ShopCustomerAccountSubSectionCacheControlSubscriber $shopCustomerAccountSubSectionCacheControlSubscriber;

    protected function setUp(): void
    {
        $this->sectionProviderMock = $this->createMock(SectionProviderInterface::class);
        $this->shopCustomerAccountSubSectionCacheControlSubscriber = new ShopCustomerAccountSubSectionCacheControlSubscriber($this->sectionProviderMock);
    }

    public function testSubscribesToKernelResponseEvent(): void
    {
        $this->assertSame([KernelEvents::RESPONSE => 'setCacheControlDirectives'], $this->shopCustomerAccountSubSectionCacheControlSubscriber::getSubscribedEvents());
    }

    public function testAddsCacheControlDirectivesToCustomerAccountRequests(): void
    {
        /** @var HttpKernelInterface|MockObject MockObject $kernelMock */
        $kernelMock = $this->createMock(HttpKernelInterface::class);
        /** @var Request|MockObject MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        /** @var Response|MockObject MockObject $responseMock */
        $responseMock = $this->createMock(Response::class);
        /** @var ResponseHeaderBag|MockObject MockObject $responseHeaderBagMock */
        $responseHeaderBagMock = $this->createMock(ResponseHeaderBag::class);
        /** @var ShopCustomerAccountSubSection|MockObject MockObject $customerAccountSubSectionMock */
        $customerAccountSubSectionMock = $this->createMock(ShopCustomerAccountSubSection::class);
        $this->sectionProviderMock->expects($this->once())->method('getSection')->willReturn($customerAccountSubSectionMock);
        $responseMock->headers = $responseHeaderBagMock;
        $event = new ResponseEvent(
            $kernelMock,
            $requestMock,
            KernelInterface::MAIN_REQUEST,
            $responseMock,
        );
        $responseHeaderBagMock->expects($this->exactly(4))->method('addCacheControlDirective')->willReturnMap([['no-cache', true], ['max-age', '0'], ['must-revalidate', true], ['no-store', true]]);
        $this->shopCustomerAccountSubSectionCacheControlSubscriber->setCacheControlDirectives($event);
    }

    public function testDoesNothingIfSectionIsDifferentThanCustomerAccount(): void
    {
        /** @var HttpKernelInterface|MockObject MockObject $kernelMock */
        $kernelMock = $this->createMock(HttpKernelInterface::class);
        /** @var Request|MockObject MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        /** @var Response|MockObject MockObject $responseMock */
        $responseMock = $this->createMock(Response::class);
        /** @var ResponseHeaderBag|MockObject MockObject $responseHeaderBagMock */
        $responseHeaderBagMock = $this->createMock(ResponseHeaderBag::class);
        /** @var SectionInterface|MockObject MockObject $sectionMock */
        $sectionMock = $this->createMock(SectionInterface::class);
        $this->sectionProviderMock->expects($this->once())->method('getSection')->willReturn($sectionMock);
        $responseMock->headers = $responseHeaderBagMock;
        $event = new ResponseEvent(
            $kernelMock,
            $requestMock,
            KernelInterface::MAIN_REQUEST,
            $responseMock,
        );
        $responseHeaderBagMock->expects($this->never())->method('addCacheControlDirective');
        $this->shopCustomerAccountSubSectionCacheControlSubscriber->setCacheControlDirectives($event);
    }
}
