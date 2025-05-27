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
use Sylius\Bundle\ShopBundle\EventListener\NonChannelLocaleListener;
use Sylius\Component\Locale\Provider\LocaleProviderInterface;
use Symfony\Bundle\SecurityBundle\Security\FirewallConfig;
use Symfony\Bundle\SecurityBundle\Security\FirewallMap;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\RouterInterface;

final class NonChannelLocaleListenerTest extends TestCase
{
    /** @var RouterInterface|MockObject */
    private $router;

    /** @var LocaleProviderInterface|MockObject */
    private $localeProvider;

    /** @var FirewallMap|MockObject */
    private $firewallMap;

    private NonChannelLocaleListener $listener;

    protected function setUp(): void
    {
        $this->router = $this->createMock(RouterInterface::class);
        $this->localeProvider = $this->createMock(LocaleProviderInterface::class);
        $this->firewallMap = $this->createMock(FirewallMap::class);
    }

    public function testItThrowsExceptionOnInstantiationWithNoFirewallNames(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new NonChannelLocaleListener(
            $this->router,
            $this->localeProvider,
            $this->firewallMap,
            []
        );
    }

    public function testItThrowsExceptionOnInstantiationWithNonStringFirewallNames(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new NonChannelLocaleListener(
            $this->router,
            $this->localeProvider,
            $this->firewallMap,
            [new \DateTime(), 1, 5.0]
        );
    }

    public function testItDoesNothingIfNotMainRequest(): void
    {
        $event = $this->createMock(RequestEvent::class);
        $event->expects($this->once())
            ->method('isMainRequest')
            ->willReturn(false);

        $event->expects($this->never())->method('getRequest');
        $this->firewallMap->expects($this->never())->method('getFirewallConfig');
        $this->localeProvider->expects($this->never())->method('getAvailableLocalesCodes');

        $this->listener = new NonChannelLocaleListener(
            $this->router,
            $this->localeProvider,
            $this->firewallMap,
            ['shop']
        );

        $this->listener->restrictRequestLocale($event);

        $this->assertTrue(true);
    }

    public function testItDoesNothingIfRequestBehindNoFirewall(): void
    {
        $request = $this->createMock(Request::class);
        $request->attributes = new ParameterBag(['_locale' => 'en']);
        $event = $this->createMock(RequestEvent::class);

        $event->expects($this->once())
            ->method('isMainRequest')
            ->willReturn(true);
        $event->expects($this->once())
            ->method('getRequest')
            ->willReturn($request);

        $this->firewallMap->expects($this->once())
            ->method('getFirewallConfig')
            ->with($request)
            ->willReturn(null);

        $this->localeProvider->expects($this->never())
            ->method('getAvailableLocalesCodes');

        $this->listener = new NonChannelLocaleListener(
            $this->router,
            $this->localeProvider,
            $this->firewallMap,
            ['shop']
        );

        $this->listener->restrictRequestLocale($event);

        $this->assertTrue(true);
    }

    public function testItDoesNothingIfFirewallNotInAllowedList(): void
    {
        $request = $this->createMock(Request::class);
        $request->attributes = new ParameterBag(['_locale' => 'en']);
        $event = $this->createMock(RequestEvent::class);

        $event->expects($this->once())
            ->method('isMainRequest')
            ->willReturn(true);
        $event->expects($this->once())
            ->method('getRequest')
            ->willReturn($request);

        $this->firewallMap->expects($this->once())
            ->method('getFirewallConfig')
            ->with($request)
            ->willReturn(new FirewallConfig('lalaland', 'mock'));

        $this->localeProvider->expects($this->never())
            ->method('getAvailableLocalesCodes');

        $this->listener = new NonChannelLocaleListener(
            $this->router,
            $this->localeProvider,
            $this->firewallMap,
            ['shop']
        );

        $this->listener->restrictRequestLocale($event);

        $this->assertTrue(true);
    }

    public function testItDoesNothingIfRequestLocaleIsInProvider(): void
    {
        $request = $this->createMock(Request::class);
        $request->attributes = new ParameterBag(['_locale' => 'en']);
        $event = $this->createMock(RequestEvent::class);

        $event->expects($this->once())
            ->method('isMainRequest')
            ->willReturn(true);
        $event->expects($this->once())
            ->method('getRequest')
            ->willReturn($request);

        $firewallConfig = new FirewallConfig('shop', 'mock');
        $this->firewallMap->expects($this->once())
            ->method('getFirewallConfig')
            ->with($request)
            ->willReturn($firewallConfig);

        $request->expects($this->once())
            ->method('getLocale')
            ->willReturn('en');

        $this->localeProvider->expects($this->once())
            ->method('getAvailableLocalesCodes')
            ->willReturn(['en', 'ga_IE']);

        $this->listener = new NonChannelLocaleListener(
            $this->router,
            $this->localeProvider,
            $this->firewallMap,
            ['shop']
        );

        $this->listener->restrictRequestLocale($event);

        $this->assertTrue(true);
    }

    public function testItRedirectsToDefaultLocaleIfRequestLocaleNotInProvider(): void
    {
        $request = $this->createMock(Request::class);
        $event = $this->createMock(RequestEvent::class);

        $event->expects($this->once())
            ->method('isMainRequest')
            ->willReturn(true);
        $event->expects($this->once())
            ->method('getRequest')
            ->willReturn($request);

        $firewallConfig = new FirewallConfig('shop', 'mock');
        $this->firewallMap->expects($this->once())
            ->method('getFirewallConfig')
            ->with($request)
            ->willReturn($firewallConfig);

        $request->expects($this->once())
            ->method('getLocale')
            ->willReturn('en');

        $request->attributes = new ParameterBag(['_locale' => 'en']);

        $this->localeProvider->expects($this->once())
            ->method('getAvailableLocalesCodes')
            ->willReturn(['ga', 'ga_IE']);
        $this->localeProvider->expects($this->once())
            ->method('getDefaultLocaleCode')
            ->willReturn('ga');

        $this->router->expects($this->once())
            ->method('generate')
            ->with('sylius_shop_homepage', ['_locale' => 'ga'])
            ->willReturn('/ga/');

        $event->expects($this->once())
            ->method('setResponse')
            ->with($this->callback(function ($response) {
                return $response instanceof RedirectResponse && $response->getTargetUrl() === '/ga/';
            }));

        $this->listener = new NonChannelLocaleListener(
            $this->router,
            $this->localeProvider,
            $this->firewallMap,
            ['shop']
        );

        $this->listener->restrictRequestLocale($event);
    }

    public function testItDoesNothingIfRequestAttributesHasNoLocale(): void
    {
        $request = $this->createMock(Request::class);
        $request->attributes = new ParameterBag();
        $event = $this->createMock(RequestEvent::class);

        $event->expects($this->once())
            ->method('isMainRequest')
            ->willReturn(true);
        $event->expects($this->once())
            ->method('getRequest')
            ->willReturn($request);

        $request->expects($this->never())
            ->method('getLocale');


        $this->listener = new NonChannelLocaleListener(
            $this->router,
            $this->localeProvider,
            $this->firewallMap,
            ['shop']
        );

        $this->listener->restrictRequestLocale($event);
    }
}
