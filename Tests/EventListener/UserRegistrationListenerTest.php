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
use Sylius\Bundle\ShopBundle\EventListener\UserRegistrationListener;
use Sylius\Bundle\UserBundle\UserEvents;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\ShopUserInterface;
use Sylius\Component\User\Security\Generator\GeneratorInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

final class UserRegistrationListenerTest extends TestCase
{
    /** @var ObjectManager|MockObject */
    private MockObject $userManagerMock;

    /** @var GeneratorInterface|MockObject */
    private MockObject $tokenGeneratorMock;

    /** @var EventDispatcherInterface|MockObject */
    private MockObject $eventDispatcherMock;

    /** @var ChannelContextInterface|MockObject */
    private MockObject $channelContextMock;

    /** @var Security|MockObject */
    private MockObject $securityMock;

    private UserRegistrationListener $userRegistrationListener;

    protected function setUp(): void
    {
        $this->userManagerMock = $this->createMock(ObjectManager::class);
        $this->tokenGeneratorMock = $this->createMock(GeneratorInterface::class);
        $this->eventDispatcherMock = $this->createMock(EventDispatcherInterface::class);
        $this->channelContextMock = $this->createMock(ChannelContextInterface::class);
        $this->securityMock = $this->createMock(Security::class);
        $this->userRegistrationListener = new UserRegistrationListener($this->userManagerMock, $this->tokenGeneratorMock, $this->eventDispatcherMock, $this->channelContextMock, $this->securityMock, 'shop');
    }

    public function testSendsAnUserVerificationEmail(): void
    {
        /** @var GenericEvent|MockObject MockObject $eventMock */
        $eventMock = $this->createMock(GenericEvent::class);
        /** @var CustomerInterface|MockObject MockObject $customerMock */
        $customerMock = $this->createMock(CustomerInterface::class);
        /** @var ShopUserInterface|MockObject MockObject $userMock */
        $userMock = $this->createMock(ShopUserInterface::class);
        /** @var ChannelInterface|MockObject MockObject $channelMock */
        $channelMock = $this->createMock(ChannelInterface::class);
        $eventMock->expects($this->once())->method('getSubject')->willReturn($customerMock);
        $customerMock->expects($this->once())->method('getUser')->willReturn($userMock);
        $this->channelContextMock->expects($this->once())->method('getChannel')->willReturn($channelMock);
        $channelMock->expects($this->once())->method('isAccountVerificationRequired')->willReturn(true);
        $this->tokenGeneratorMock->expects($this->once())->method('generate')->willReturn('1d7dbc5c3dbebe5c');
        $userMock->expects($this->once())->method('setEmailVerificationToken')->with('1d7dbc5c3dbebe5c');
        $this->userManagerMock->expects($this->once())->method('persist')->with($userMock);
        $this->userManagerMock->expects($this->once())->method('flush');
        $this->eventDispatcherMock->expects($this->once())->method('dispatch')->with($this->isInstanceOf(GenericEvent::class), UserEvents::REQUEST_VERIFICATION_TOKEN)
        ;
        $this->userRegistrationListener->handleUserVerification($eventMock);
    }

    public function testEnablesAndSignsInUser(): void
    {
        /** @var GenericEvent|MockObject MockObject $eventMock */
        $eventMock = $this->createMock(GenericEvent::class);
        /** @var CustomerInterface|MockObject MockObject $customerMock */
        $customerMock = $this->createMock(CustomerInterface::class);
        /** @var ShopUserInterface|MockObject MockObject $userMock */
        $userMock = $this->createMock(ShopUserInterface::class);
        /** @var ChannelInterface|MockObject MockObject $channelMock */
        $channelMock = $this->createMock(ChannelInterface::class);
        $eventMock->expects($this->once())->method('getSubject')->willReturn($customerMock);
        $customerMock->expects($this->once())->method('getUser')->willReturn($userMock);
        $this->channelContextMock->expects($this->once())->method('getChannel')->willReturn($channelMock);
        $channelMock->expects($this->once())->method('isAccountVerificationRequired')->willReturn(false);
        $userMock->expects($this->once())->method('setEnabled')->with(true);
        $this->userManagerMock->expects($this->once())->method('persist')->with($userMock);
        $this->userManagerMock->expects($this->once())->method('flush');
        $this->securityMock->expects($this->once())->method('login')->with($userMock, 'form_login', 'shop');
        $this->tokenGeneratorMock->expects($this->never())->method('generate');
        $userMock->expects($this->never())->method('setEmailVerificationToken');
        $this->eventDispatcherMock->expects($this->never())->method('dispatch')->with($this->isInstanceOf(GenericEvent::class), UserEvents::REQUEST_VERIFICATION_TOKEN)
        ;
        $this->userRegistrationListener->handleUserVerification($eventMock);
    }

    public function testDoesNotSendVerificationEmailIfItIsNotRequiredOnChannel(): void
    {
        /** @var GenericEvent|MockObject MockObject $eventMock */
        $eventMock = $this->createMock(GenericEvent::class);
        /** @var CustomerInterface|MockObject MockObject $customerMock */
        $customerMock = $this->createMock(CustomerInterface::class);
        /** @var ShopUserInterface|MockObject MockObject $userMock */
        $userMock = $this->createMock(ShopUserInterface::class);
        /** @var ChannelInterface|MockObject MockObject $channelMock */
        $channelMock = $this->createMock(ChannelInterface::class);
        $eventMock->expects($this->once())->method('getSubject')->willReturn($customerMock);
        $customerMock->expects($this->once())->method('getUser')->willReturn($userMock);
        $this->channelContextMock->expects($this->once())->method('getChannel')->willReturn($channelMock);
        $channelMock->expects($this->once())->method('isAccountVerificationRequired')->willReturn(false);
        $userMock->expects($this->once())->method('setEnabled')->with(true);
        $this->userManagerMock->expects($this->once())->method('persist')->with($userMock);
        $this->userManagerMock->expects($this->once())->method('flush');
        $this->securityMock->expects($this->once())->method('login')->with($userMock, 'form_login', 'shop');
        $this->tokenGeneratorMock->expects($this->never())->method('generate');
        $userMock->expects($this->never())->method('setEmailVerificationToken');
        $this->eventDispatcherMock->expects($this->never())->method('dispatch')->with($this->isInstanceOf(GenericEvent::class), UserEvents::REQUEST_VERIFICATION_TOKEN)
        ;
        $this->userRegistrationListener->handleUserVerification($eventMock);
    }

    public function testThrowsAnInvalidArgumentExceptionIfEventSubjectIsNotCustomerType(): void
    {
        /** @var GenericEvent|MockObject MockObject $eventMock */
        $eventMock = $this->createMock(GenericEvent::class);
        /** @var stdClass|MockObject MockObject $customerMock */
        $customerMock = $this->createMock(stdClass::class);
        $eventMock->expects($this->once())->method('getSubject')->willReturn($customerMock);
        $this->expectException(InvalidArgumentException::class);
        $this->userRegistrationListener->handleUserVerification($eventMock);
    }

    public function testThrowsAnInvalidArgumentExceptionIfUserIsNull(): void
    {
        /** @var GenericEvent|MockObject MockObject $eventMock */
        $eventMock = $this->createMock(GenericEvent::class);
        /** @var CustomerInterface|MockObject MockObject $customerMock */
        $customerMock = $this->createMock(CustomerInterface::class);
        $eventMock->expects($this->once())->method('getSubject')->willReturn($customerMock);
        $customerMock->expects($this->once())->method('getUser')->willReturn(null);
        $this->expectException(InvalidArgumentException::class);
        $this->userRegistrationListener->handleUserVerification($eventMock);
    }
}
