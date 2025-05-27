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

namespace Tests\Sylius\Bundle\ShopBundle\Theme;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\ShopBundle\Theme\ChannelBasedThemeContext;
use Sylius\Bundle\ThemeBundle\Context\ThemeContextInterface;
use Sylius\Bundle\ThemeBundle\Model\ThemeInterface;
use Sylius\Bundle\ThemeBundle\Repository\ThemeRepositoryInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Context\ChannelNotFoundException;
use Sylius\Component\Core\Model\ChannelInterface;

final class ChannelBasedThemeContextTest extends TestCase
{
    /** @var ChannelContextInterface|MockObject */
    private MockObject $channelContextMock;

    /** @var ThemeRepositoryInterface|MockObject */
    private MockObject $themeRepositoryMock;

    private ChannelBasedThemeContext $channelBasedThemeContext;

    protected function setUp(): void
    {
        $this->channelContextMock = $this->createMock(ChannelContextInterface::class);
        $this->themeRepositoryMock = $this->createMock(ThemeRepositoryInterface::class);

        $this->channelBasedThemeContext = new ChannelBasedThemeContext(
            $this->channelContextMock,
            $this->themeRepositoryMock,
        );
    }

    public function testImplementsThemeContextInterface(): void
    {
        $this->assertInstanceOf(ThemeContextInterface::class, $this->channelBasedThemeContext);
    }

    public function testReturnsATheme(): void
    {
        /** @var ChannelInterface|MockObject MockObject $channelMock */
        $channelMock = $this->createMock(ChannelInterface::class);
        /** @var ThemeInterface|MockObject MockObject $themeMock */
        $themeMock = $this->createMock(ThemeInterface::class);
        $this->channelContextMock->expects($this->once())->method('getChannel')->willReturn($channelMock);
        $channelMock->expects($this->once())->method('getThemeName')->willReturn('theme/name');
        $this->themeRepositoryMock->expects($this->once())->method('findOneByName')->with('theme/name')->willReturn($themeMock);
        $this->assertSame($themeMock, $this->channelBasedThemeContext->getTheme());
    }

    public function testReturnsNullIfChannelHasNoTheme(): void
    {
        /** @var ChannelInterface|MockObject MockObject $channelMock */
        $channelMock = $this->createMock(ChannelInterface::class);
        $this->channelContextMock->expects($this->once())->method('getChannel')->willReturn($channelMock);
        $channelMock->expects($this->once())->method('getThemeName')->willReturn(null);
        $this->themeRepositoryMock->expects($this->never())->method('findOneByName');
        $this->assertNull($this->channelBasedThemeContext->getTheme());
    }

    public function testReturnsPreviouslyFoundTheme(): void
    {
        /** @var ThemeInterface|MockObject $themeMock */
        $themeMock = $this->createMock(ThemeInterface::class);

        // Ustawiamy prywatną właściwość "theme" bezpośrednio na obiekcie testowanym
        $reflection = new \ReflectionObject($this->channelBasedThemeContext);
        $property = $reflection->getProperty('theme');
        $property->setAccessible(true);
        $property->setValue($this->channelBasedThemeContext, $themeMock);

        // Oczekujemy, że nie zostaną wywołane metody, bo theme już jest ustawione
        $this->channelContextMock->expects($this->never())->method('getChannel');
        $this->themeRepositoryMock->expects($this->never())->method('findOneByName');

        // Sprawdzenie, czy zwrócony temat to ten sam obiekt
        $this->assertSame($themeMock, $this->channelBasedThemeContext->getTheme());
    }

    public function testReturnsNullIfTheThemeWasNotFoundPreviously(): void
    {
        $reflection = new \ReflectionObject($this->channelBasedThemeContext);
        $property = $reflection->getProperty('theme');
        $property->setAccessible(true);
        $property->setValue($this->channelBasedThemeContext, null);

        $this->channelContextMock->expects($this->never())->method('getChannel');
        $this->themeRepositoryMock->expects($this->never())->method('findOneByName');

        $this->assertNull($this->channelBasedThemeContext->getTheme());
    }

    public function testReturnsNullIfThereIsNoChannel(): void
    {
        $this->channelContextMock->expects($this->once())->method('getChannel')->willThrowException(new ChannelNotFoundException());
        $this->assertNull($this->channelBasedThemeContext->getTheme());
    }

    public function testReturnsNullIfAnyExceptionIsThrownDuringGettingTheChannel(): void
    {
        $this->channelContextMock->expects($this->once())->method('getChannel')->willThrowException(new \Exception());
        $this->assertNull($this->channelBasedThemeContext->getTheme());
    }
}
