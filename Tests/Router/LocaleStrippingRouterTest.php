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

namespace Tests\Sylius\Bundle\ShopBundle\Router;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\ShopBundle\Router\LocaleStrippingRouter;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\CacheWarmer\WarmableInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\RouterInterface;

final class LocaleStrippingRouterTest extends TestCase
{
    /** @var RouterInterface|MockObject */
    private MockObject $decoratedRouterMock;

    /** @var LocaleContextInterface|MockObject */
    private MockObject $localeContextMock;

    private LocaleStrippingRouter $localeStrippingRouter;

    protected function setUp(): void
    {
        $this->decoratedRouterMock = $this->createMock(RouterInterface::class);
        $this->localeContextMock = $this->createMock(LocaleContextInterface::class);
        $this->localeStrippingRouter = new LocaleStrippingRouter($this->decoratedRouterMock, $this->localeContextMock);
    }

    public function testASymfonyRouter(): void
    {
        $this->assertInstanceOf(RouterInterface::class, $this->localeStrippingRouter);
    }

    public function testWarmable(): void
    {
        $this->assertInstanceOf(WarmableInterface::class, $this->localeStrippingRouter);

        $this->localeStrippingRouter->warmUp('/cache/dir');
    }

    public function testDelegatesPathInfoMathingToInnerRouter(): void
    {
        $this->decoratedRouterMock->expects($this->once())->method('match')->with('/path/info')->willReturn(['matched' => true]);
        $this->assertSame(['matched' => true], $this->localeStrippingRouter->match('/path/info'));
    }

    public function testDelegatesRequestMatchingToInnerRouterPathInfoMatchingWhenItDoesNotImplementRequestMatcherInterface(): void
    {
        /** @var Request|MockObject MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        $requestMock->expects($this->once())->method('getPathInfo')->willReturn('/path/info');
        $this->decoratedRouterMock->expects($this->once())->method('match')->with('/path/info')->willReturn(['matched' => true]);
        $this->assertSame(['matched' => true], $this->localeStrippingRouter->matchRequest($requestMock));
    }

    public function testDelegatesRequestMatchingToInnerRouter(): void
    {
        /** @var RouterInterface&RequestMatcherInterface&MockObject $routerMock */
        $routerMock = $this->createMockForIntersectionOfInterfaces([
            RouterInterface::class,
            RequestMatcherInterface::class,
        ]);
        /** @var Request|MockObject MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);

        $this->localeStrippingRouter = new LocaleStrippingRouter($routerMock, $this->localeContextMock);
        $routerMock->expects($this->never())->method('match');
        $routerMock->expects($this->once())->method('matchRequest')->with($requestMock)->willReturn(['matched' => true]);
        $this->assertSame(['matched' => true], $this->localeStrippingRouter->matchRequest($requestMock));
    }

    public function testStripsLocaleFromTheGeneratedUrlIfLocaleIsTheSameAsTheOneFromContext(): void
    {
        $this->localeContextMock->expects($this->exactly(4))->method('getLocaleCode')->willReturn('pl_PL');
        $this->decoratedRouterMock->expects($this->exactly(4))->method('generate')->with('route_name', [], UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn(
                'https://generated.url/?_locale=pl_PL',
                'https://generated.url/?foo=bar&_locale=pl_PL',
                'https://generated.url/?_locale=pl_PL&foo=bar',
                'https://generated.url/?bar=foo&_locale=pl_PL&foo=bar',
            )
        ;
        $this->assertSame('https://generated.url/', $this->localeStrippingRouter->generate('route_name'));
        $this->assertSame('https://generated.url/?foo=bar', $this->localeStrippingRouter->generate('route_name'));
        $this->assertSame('https://generated.url/?foo=bar', $this->localeStrippingRouter->generate('route_name'));
        $this->assertSame('https://generated.url/?bar=foo&foo=bar', $this->localeStrippingRouter->generate('route_name'));
    }

    public function testDoesNotStripLocaleFromTheGeneratedUrlIfLocaleIsDifferentThanTheOneFromContext(): void
    {
        $this->localeContextMock->expects($this->once())->method('getLocaleCode')->willReturn('en_US');
        $this->decoratedRouterMock->expects($this->once())->method('generate')->with('route_name', [], UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn('https://generated.url/?_locale=pl_PL')
        ;
        $this->assertSame('https://generated.url/?_locale=pl_PL', $this->localeStrippingRouter->generate('route_name'));
    }

    public function testDoesNotStirpLocaleFromTheGeneratedUrlIfThereIsNoLocaleParameter(): void
    {
        $this->decoratedRouterMock->expects($this->once())->method('generate')->with('route_name', [], UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn('https://generated.url/')
        ;
        $this->assertSame('https://generated.url/', $this->localeStrippingRouter->generate('route_name'));
    }
}
