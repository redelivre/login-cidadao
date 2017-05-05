<?php
/**
 * This file is part of the login-cidadao project or it's bundles.
 *
 * (c) Guilherme Donato <guilhermednt on github>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LoginCidadao\PhoneVerificationBundle\Tests\Event;

use FOS\UserBundle\FOSUserEvents;
use LoginCidadao\PhoneVerificationBundle\Event\UserRegistrationSubscriber;

class UserRegistrationSubscriberTest extends \PHPUnit_Framework_TestCase
{
    public function testGetSubscribedEvents()
    {
        $this->assertArrayHasKey(
            FOSUserEvents::REGISTRATION_COMPLETED,
            UserRegistrationSubscriber::getSubscribedEvents()
        );
    }

    public function testOnRegistrationCompletedWithPhone()
    {
        $phone = $this->getMock('libphonenumber\PhoneNumber');
        $user = $this->getMock('LoginCidadao\CoreBundle\Model\PersonInterface');
        $user->expects($this->exactly(2))->method('getMobile')->willReturn($phone);

        $verificationService = $this->getMock(
            'LoginCidadao\PhoneVerificationBundle\Service\PhoneVerificationServiceInterface'
        );
        $verificationService->expects($this->once())->method('enforcePhoneVerification')->with($user, $phone);

        $event = $this->getMockBuilder('FOS\UserBundle\Event\FilterUserResponseEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())->method('getUser')->willReturn($user);

        $subscriber = new UserRegistrationSubscriber($verificationService);
        $subscriber->onRegistrationCompleted($event);
    }

    public function testOnRegistrationCompletedWithoutPhone()
    {
        $user = $this->getMock('LoginCidadao\CoreBundle\Model\PersonInterface');
        $user->expects($this->once())->method('getMobile')->willReturn(null);

        $verificationService = $this->getMock(
            'LoginCidadao\PhoneVerificationBundle\Service\PhoneVerificationServiceInterface'
        );

        $event = $this->getMockBuilder('FOS\UserBundle\Event\FilterUserResponseEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())->method('getUser')->willReturn($user);

        $subscriber = new UserRegistrationSubscriber($verificationService);
        $subscriber->onRegistrationCompleted($event);
    }
}
