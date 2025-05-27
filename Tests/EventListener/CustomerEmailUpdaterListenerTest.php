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
use Sylius\Bundle\CoreBundle\SectionResolver\SectionInterface;
use Sylius\Bundle\CoreBundle\SectionResolver\SectionProviderInterface;
use Sylius\Bundle\ShopBundle\EventListener\CustomerEmailUpdaterListener;
use Sylius\Bundle\ShopBundle\SectionResolver\ShopSection;
use Sylius\Bundle\UserBundle\UserEvents;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\ShopUserInterface;
use Sylius\Component\User\Security\Generator\GeneratorInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class CustomerEmailUpdaterListenerTest extends TestCase
{
    /** @var GeneratorInterface|MockObject */
    private MockObject $tokenGeneratorMock;

    /** @var ChannelContextInterface|MockObject */
    private MockObject $channelContextMock;

    /** @var EventDispatcherInterface|MockObject */
    private MockObject $eventDispatcherMock;

    /** @var RequestStack|MockObject */
    private MockObject $requestStackMock;

    /** @var SectionProviderInterface|MockObject */
    private MockObject $sectionResolverMock;

    /** @var TokenStorageInterface|MockObject */
    private MockObject $tokenStorageMock;

    private CustomerEmailUpdaterListener $customerEmailUpdaterListener;

    protected function setUp(): void
    {
        $this->tokenGeneratorMock = $this->createMock(GeneratorInterface::class);
        $this->channelContextMock = $this->createMock(ChannelContextInterface::class);
        $this->eventDispatcherMock = $this->createMock(EventDispatcherInterface::class);
        $this->requestStackMock = $this->createMock(RequestStack::class);
        $this->sectionResolverMock = $this->createMock(SectionProviderInterface::class);
        $this->tokenStorageMock = $this->createMock(TokenStorageInterface::class);
        $this->customerEmailUpdaterListener = new CustomerEmailUpdaterListener($this->tokenGeneratorMock, $this->channelContextMock, $this->eventDispatcherMock, $this->requestStackMock, $this->sectionResolverMock, $this->tokenStorageMock);
    }

    public function testDoesNothingChangeWasPerformedByAdminDuringEraseVerification(): void
    {
        /** @var SectionInterface|MockObject MockObject $sectionMock */
        $sectionMock = $this->createMock(SectionInterface::class);
        /** @var GenericEvent|MockObject MockObject $eventMock */
        $eventMock = $this->createMock(GenericEvent::class);
        $this->sectionResolverMock->expects($this->once())->method('getSection')->willReturn($sectionMock);
        $eventMock->expects($this->never())->method('getSubject');
        $this->customerEmailUpdaterListener->eraseVerification($eventMock);
    }

    public function testDoesNothingChangeWasPerformedByAdminDuringSendVerificationEmail(): void
    {
        /** @var SectionInterface|MockObject MockObject $sectionMock */
        $sectionMock = $this->createMock(SectionInterface::class);
        /** @var GenericEvent|MockObject MockObject $eventMock */
        $eventMock = $this->createMock(GenericEvent::class);
        $this->sectionResolverMock->expects($this->once())->method('getSection')->willReturn($sectionMock);
        $eventMock->expects($this->never())->method('getSubject');
        $this->customerEmailUpdaterListener->sendVerificationEmail($eventMock);
    }

    public function testRemovesUserVerificationAndDisablesUserIfEmailHasBeenChangedAndChannelRequiresVerification(): void
    {
        /** @var GenericEvent|MockObject MockObject $eventMock */
        $eventMock = $this->createMock(GenericEvent::class);
        /** @var CustomerInterface|MockObject MockObject $customerMock */
        $customerMock = $this->createMock(CustomerInterface::class);
        /** @var ShopUserInterface|MockObject MockObject $userMock */
        $userMock = $this->createMock(ShopUserInterface::class);
        /** @var ChannelInterface|MockObject MockObject $channelMock */
        $channelMock = $this->createMock(ChannelInterface::class);
        /** @var ShopSection|MockObject MockObject $shopSectionMock */
        $shopSectionMock = $this->createMock(ShopSection::class);
        $this->sectionResolverMock->expects($this->once())->method('getSection')->willReturn($shopSectionMock);
        $eventMock->expects($this->once())->method('getSubject')->willReturn($customerMock);
        $customerMock->expects($this->once())->method('getUser')->willReturn($userMock);
        $customerMock->expects($this->once())->method('getEmail')->willReturn('new@example.com');
        $userMock->expects($this->once())->method('getUsername')->willReturn('old@example.com');
        $this->tokenGeneratorMock->expects($this->once())->method('generate')->willReturn('1d7dbc5c3dbebe5c');
        $this->channelContextMock->expects($this->once())->method('getChannel')->willReturn($channelMock);
        $channelMock->expects($this->once())->method('isAccountVerificationRequired')->willReturn(true);
        $userMock->expects($this->once())->method('setVerifiedAt')->with(null);
        $userMock->expects($this->once())->method('setEmailVerificationToken')->with('1d7dbc5c3dbebe5c');
        $userMock->expects($this->once())->method('setEnabled')->with(false);
        $this->tokenStorageMock->expects($this->once())->method('setToken')->with(null);
        $this->customerEmailUpdaterListener->eraseVerification($eventMock);
    }

    public function testRemovesUserVerificationOnlyIfEmailHasBeenChangedButChannelDoesNotRequireVerification(): void
    {
        /** @var GenericEvent|MockObject MockObject $eventMock */
        $eventMock = $this->createMock(GenericEvent::class);
        /** @var CustomerInterface|MockObject MockObject $customerMock */
        $customerMock = $this->createMock(CustomerInterface::class);
        /** @var ShopUserInterface|MockObject MockObject $userMock */
        $userMock = $this->createMock(ShopUserInterface::class);
        /** @var ChannelInterface|MockObject MockObject $channelMock */
        $channelMock = $this->createMock(ChannelInterface::class);
        /** @var ShopSection|MockObject MockObject $shopSectionMock */
        $shopSectionMock = $this->createMock(ShopSection::class);
        $this->sectionResolverMock->expects($this->once())->method('getSection')->willReturn($shopSectionMock);
        $eventMock->expects($this->once())->method('getSubject')->willReturn($customerMock);
        $customerMock->expects($this->once())->method('getUser')->willReturn($userMock);
        $customerMock->expects($this->once())->method('getEmail')->willReturn('new@example.com');
        $userMock->expects($this->once())->method('getUsername')->willReturn('old@example.com');
        $this->channelContextMock->expects($this->once())->method('getChannel')->willReturn($channelMock);
        $channelMock->expects($this->once())->method('isAccountVerificationRequired')->willReturn(false);
        $userMock->expects($this->once())->method('setVerifiedAt')->with(null);
        $this->tokenGeneratorMock->expects($this->never())->method('generate');
        $userMock->expects($this->never())->method('setEmailVerificationToken');
        $userMock->expects($this->never())->method('setEnabled')->with(false);
        $this->tokenStorageMock->expects($this->never())->method('setToken')->with(null);
        $this->customerEmailUpdaterListener->eraseVerification($eventMock);
    }

    public function testDoesNothingIfEmailHasNotBeenChanged(): void
    {
        /** @var GenericEvent|MockObject MockObject $eventMock */
        $eventMock = $this->createMock(GenericEvent::class);
        /** @var CustomerInterface|MockObject MockObject $customerMock */
        $customerMock = $this->createMock(CustomerInterface::class);
        /** @var ShopUserInterface|MockObject MockObject $userMock */
        $userMock = $this->createMock(ShopUserInterface::class);
        /** @var ShopSection|MockObject MockObject $shopSectionMock */
        $shopSectionMock = $this->createMock(ShopSection::class);
        $this->sectionResolverMock->expects($this->once())->method('getSection')->willReturn($shopSectionMock);
        $eventMock->expects($this->once())->method('getSubject')->willReturn($customerMock);
        $customerMock->expects($this->once())->method('getUser')->willReturn($userMock);
        $customerMock->expects($this->once())->method('getEmail')->willReturn('new@example.com');
        $userMock->expects($this->once())->method('getUsername')->willReturn('new@example.com');
        $this->channelContextMock->expects($this->never())->method('getChannel');
        $this->tokenGeneratorMock->expects($this->never())->method('generate');
        $userMock->expects($this->never())->method('setVerifiedAt')->with(null);
        $userMock->expects($this->never())->method('setEmailVerificationToken');
        $userMock->expects($this->never())->method('setEnabled')->with(false);
        $this->tokenStorageMock->expects($this->never())->method('setToken')->with(null);
        $this->customerEmailUpdaterListener->eraseVerification($eventMock);
    }

    public function testThrowsAnInvalidArgumentExceptionIfEventSubjectIsNotCustomerType(): void
    {
        /** @var GenericEvent|MockObject MockObject $eventMock */
        $eventMock = $this->createMock(GenericEvent::class);
        /** @var stdClass|MockObject MockObject $customerMock */
        $customerMock = $this->createMock(stdClass::class);
        /** @var ShopSection|MockObject MockObject $shopSectionMock */
        $shopSectionMock = $this->createMock(ShopSection::class);
        $this->sectionResolverMock->expects($this->once())->method('getSection')->willReturn($shopSectionMock);
        $eventMock->expects($this->once())->method('getSubject')->willReturn($customerMock);
        $this->expectException(InvalidArgumentException::class);
        $this->expectException(InvalidArgumentException::class);
        $this->customerEmailUpdaterListener->sendVerificationEmail($eventMock);
        $this->expectException(InvalidArgumentException::class);
        $this->customerEmailUpdaterListener->sendVerificationEmail($eventMock);
    }

    public function testThrowsAnInvalidArgumentExceptionIfUserIsNull(): void
    {
        /** @var GenericEvent|MockObject MockObject $eventMock */
        $eventMock = $this->createMock(GenericEvent::class);
        /** @var CustomerInterface|MockObject MockObject $customerMock */
        $customerMock = $this->createMock(CustomerInterface::class);
        /** @var ShopSection|MockObject MockObject $shopSectionMock */
        $shopSectionMock = $this->createMock(ShopSection::class);
        $this->sectionResolverMock->expects($this->once())->method('getSection')->willReturn($shopSectionMock);
        $eventMock->expects($this->once())->method('getSubject')->willReturn($customerMock);
        $customerMock->expects($this->once())->method('getUser')->willReturn(null);
        $this->expectException(InvalidArgumentException::class);
        $this->expectException(InvalidArgumentException::class);
        $this->customerEmailUpdaterListener->sendVerificationEmail($eventMock);
        $this->expectException(InvalidArgumentException::class);
        $this->customerEmailUpdaterListener->sendVerificationEmail($eventMock);
    }

    public function testSendsVerificationEmailAndAddsFlashIfUserVerificationIsRequired(): void
    {
        /** @var SessionInterface|MockObject MockObject $sessionMock */
        $sessionMock = $this->createMock(SessionInterface::class);
        /** @var GenericEvent|MockObject MockObject $eventMock */
        $eventMock = $this->createMock(GenericEvent::class);
        /** @var CustomerInterface|MockObject MockObject $customerMock */
        $customerMock = $this->createMock(CustomerInterface::class);
        /** @var ShopUserInterface|MockObject MockObject $userMock */
        $userMock = $this->createMock(ShopUserInterface::class);
        /** @var FlashBagInterface|MockObject MockObject $flashBagMock */
        $flashBagMock = $this->createMock(FlashBagInterface::class);
        /** @var ChannelInterface|MockObject MockObject $channelMock */
        $channelMock = $this->createMock(ChannelInterface::class);
        /** @var ShopSection|MockObject MockObject $shopSectionMock */
        $shopSectionMock = $this->createMock(ShopSection::class);
        $this->sectionResolverMock->expects($this->once())->method('getSection')->willReturn($shopSectionMock);
        $this->channelContextMock->expects($this->once())->method('getChannel')->willReturn($channelMock);
        $channelMock->expects($this->once())->method('isAccountVerificationRequired')->willReturn(true);
        $eventMock->expects($this->once())->method('getSubject')->willReturn($customerMock);
        $customerMock->expects($this->once())->method('getUser')->willReturn($userMock);
        $userMock->expects($this->once())->method('isEnabled')->willReturn(false);
        $userMock->expects($this->once())->method('isVerified')->willReturn(false);
        $userMock->expects($this->once())->method('getEmailVerificationToken')->willReturn('1d7dbc5c3dbebe5c');
        $this->requestStackMock->expects($this->once())->method('getSession')->willReturn($sessionMock);
        $sessionMock->expects($this->once())->method('getBag')->with('flashes')->willReturn($flashBagMock);
        $flashBagMock->expects($this->once())->method('add')->with('success', 'sylius.user.verify_email_request');
        $this->eventDispatcherMock->expects($this->once())->method('dispatch')->with($this->isInstanceOf(GenericEvent::class), UserEvents::REQUEST_VERIFICATION_TOKEN)
        ;
        $this->customerEmailUpdaterListener->sendVerificationEmail($eventMock);
    }

    public function testDoesNotSendEmailIfUserIsStillEnabled(): void
    {
        /** @var SessionInterface|MockObject MockObject $sessionMock */
        $sessionMock = $this->createMock(SessionInterface::class);
        /** @var GenericEvent|MockObject MockObject $eventMock */
        $eventMock = $this->createMock(GenericEvent::class);
        /** @var CustomerInterface|MockObject MockObject $customerMock */
        $customerMock = $this->createMock(CustomerInterface::class);
        /** @var ShopUserInterface|MockObject MockObject $userMock */
        $userMock = $this->createMock(ShopUserInterface::class);
        /** @var FlashBagInterface|MockObject MockObject $flashBagMock */
        $flashBagMock = $this->createMock(FlashBagInterface::class);
        /** @var ChannelInterface|MockObject MockObject $channelMock */
        $channelMock = $this->createMock(ChannelInterface::class);
        /** @var ShopSection|MockObject MockObject $shopSectionMock */
        $shopSectionMock = $this->createMock(ShopSection::class);
        $this->sectionResolverMock->expects($this->once())->method('getSection')->willReturn($shopSectionMock);
        $this->channelContextMock->expects($this->once())->method('getChannel')->willReturn($channelMock);
        $channelMock->expects($this->once())->method('isAccountVerificationRequired')->willReturn(true);
        $eventMock->expects($this->once())->method('getSubject')->willReturn($customerMock);
        $customerMock->expects($this->once())->method('getUser')->willReturn($userMock);
        $userMock->expects($this->once())->method('isEnabled')->willReturn(true);
        $userMock->expects($this->never())->method('isVerified');
        $userMock->expects($this->never())->method('getEmailVerificationToken');
        $sessionMock->expects($this->never())->method('getBag')->with('flashes');
        $flashBagMock->expects($this->never())->method('add');
        $this->eventDispatcherMock->expects($this->never())->method('dispatch')->with($this->isInstanceOf(GenericEvent::class), UserEvents::REQUEST_VERIFICATION_TOKEN)
        ;
        $this->customerEmailUpdaterListener->sendVerificationEmail($eventMock);
    }

    public function testDoesNotSendEmailIfUserIsStillVerified(): void
    {
        /** @var SessionInterface|MockObject MockObject $sessionMock */
        $sessionMock = $this->createMock(SessionInterface::class);
        /** @var GenericEvent|MockObject MockObject $eventMock */
        $eventMock = $this->createMock(GenericEvent::class);
        /** @var CustomerInterface|MockObject MockObject $customerMock */
        $customerMock = $this->createMock(CustomerInterface::class);
        /** @var ShopUserInterface|MockObject MockObject $userMock */
        $userMock = $this->createMock(ShopUserInterface::class);
        /** @var FlashBagInterface|MockObject MockObject $flashBagMock */
        $flashBagMock = $this->createMock(FlashBagInterface::class);
        /** @var ChannelInterface|MockObject MockObject $channelMock */
        $channelMock = $this->createMock(ChannelInterface::class);
        /** @var ShopSection|MockObject MockObject $shopSectionMock */
        $shopSectionMock = $this->createMock(ShopSection::class);

        $this->sectionResolverMock->expects($this->once())->method('getSection')->willReturn($shopSectionMock);
        $this->channelContextMock->expects($this->once())->method('getChannel')->willReturn($channelMock);
        $channelMock->expects($this->once())->method('isAccountVerificationRequired')->willReturn(true);
        $eventMock->expects($this->once())->method('getSubject')->willReturn($customerMock);
        $customerMock->expects($this->once())->method('getUser')->willReturn($userMock);
        $userMock->expects($this->once())->method('isEnabled')->willReturn(false);
        $userMock->expects($this->once())->method('isVerified')->willReturn(true);
        $userMock->expects($this->never())->method('getEmailVerificationToken');
        $sessionMock->expects($this->never())->method('getBag')->with('flashes');
        $flashBagMock->expects($this->never())->method('add');
        $this->eventDispatcherMock->expects($this->never())->method('dispatch')->with($this->isInstanceOf(GenericEvent::class), UserEvents::REQUEST_VERIFICATION_TOKEN)
        ;
        $this->customerEmailUpdaterListener->sendVerificationEmail($eventMock);
    }

    public function testDoesNotSendEmailIfUserDoesNotHaveVerificationToken(): void
    {
        /** @var SessionInterface|MockObject MockObject $sessionMock */
        $sessionMock = $this->createMock(SessionInterface::class);
        /** @var GenericEvent|MockObject MockObject $eventMock */
        $eventMock = $this->createMock(GenericEvent::class);
        /** @var CustomerInterface|MockObject MockObject $customerMock */
        $customerMock = $this->createMock(CustomerInterface::class);
        /** @var ShopUserInterface|MockObject MockObject $userMock */
        $userMock = $this->createMock(ShopUserInterface::class);
        /** @var FlashBagInterface|MockObject MockObject $flashBagMock */
        $flashBagMock = $this->createMock(FlashBagInterface::class);
        /** @var ChannelInterface|MockObject MockObject $channelMock */
        $channelMock = $this->createMock(ChannelInterface::class);
        /** @var ShopSection|MockObject MockObject $shopSectionMock */
        $shopSectionMock = $this->createMock(ShopSection::class);
        $this->sectionResolverMock->expects($this->once())->method('getSection')->willReturn($shopSectionMock);
        $this->channelContextMock->expects($this->once())->method('getChannel')->willReturn($channelMock);
        $channelMock->expects($this->once())->method('isAccountVerificationRequired')->willReturn(true);
        $eventMock->expects($this->once())->method('getSubject')->willReturn($customerMock);
        $customerMock->expects($this->once())->method('getUser')->willReturn($userMock);
        $userMock->expects($this->once())->method('isEnabled')->willReturn(false);
        $userMock->expects($this->once())->method('isVerified')->willReturn(false);
        $userMock->expects($this->once())->method('getEmailVerificationToken')->willReturn(null);
        $sessionMock->expects($this->never())->method('getBag')->with('flashes');
        $flashBagMock->expects($this->never())->method('add');
        $this->eventDispatcherMock->expects($this->never())->method('dispatch')->with($this->isInstanceOf(GenericEvent::class), UserEvents::REQUEST_VERIFICATION_TOKEN)
        ;
        $this->customerEmailUpdaterListener->sendVerificationEmail($eventMock);
    }

    public function testDoesNothingIfChannelDoesNotRequireVerification(): void
    {
        /** @var SessionInterface|MockObject MockObject $sessionMock */
        $sessionMock = $this->createMock(SessionInterface::class);
        /** @var GenericEvent|MockObject MockObject $eventMock */
        $eventMock = $this->createMock(GenericEvent::class);
        /** @var CustomerInterface|MockObject MockObject $customerMock */
        $customerMock = $this->createMock(CustomerInterface::class);
        /** @var ShopUserInterface|MockObject MockObject $userMock */
        $userMock = $this->createMock(ShopUserInterface::class);
        /** @var FlashBagInterface|MockObject MockObject $flashBagMock */
        $flashBagMock = $this->createMock(FlashBagInterface::class);
        /** @var ChannelInterface|MockObject MockObject $channelMock */
        $channelMock = $this->createMock(ChannelInterface::class);
        /** @var ShopSection|MockObject MockObject $shopSectionMock */
        $shopSectionMock = $this->createMock(ShopSection::class);
        $this->sectionResolverMock->expects($this->once())->method('getSection')->willReturn($shopSectionMock);
        $this->channelContextMock->expects($this->once())->method('getChannel')->willReturn($channelMock);
        $channelMock->expects($this->once())->method('isAccountVerificationRequired')->willReturn(false);
        $eventMock->expects($this->once())->method('getSubject')->willReturn($customerMock);
        $customerMock->expects($this->once())->method('getUser')->willReturn($userMock);
        $sessionMock->expects($this->never())->method('getBag')->with('flashes');
        $flashBagMock->expects($this->never())->method('add');
        $this->eventDispatcherMock->expects($this->never())->method('dispatch')->with($this->isInstanceOf(GenericEvent::class), UserEvents::REQUEST_VERIFICATION_TOKEN)
        ;
        $this->customerEmailUpdaterListener->sendVerificationEmail($eventMock);
    }
}
