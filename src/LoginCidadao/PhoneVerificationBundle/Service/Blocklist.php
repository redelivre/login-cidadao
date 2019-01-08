<?php
/**
 * This file is part of the login-cidadao project or it's bundles.
 *
 * (c) Guilherme Donato <guilhermednt on github>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LoginCidadao\PhoneVerificationBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use libphonenumber\PhoneNumber;
use LoginCidadao\CoreBundle\Entity\PersonRepository;
use LoginCidadao\CoreBundle\Mailer\TwigSwiftMailer;
use LoginCidadao\CoreBundle\Model\PersonInterface;
use LoginCidadao\CoreBundle\Security\User\Manager\UserManager;
use LoginCidadao\PhoneVerificationBundle\Entity\BlockedPhoneNumber;
use LoginCidadao\PhoneVerificationBundle\Entity\BlockedPhoneNumberRepository;
use LoginCidadao\PhoneVerificationBundle\Model\BlockedPhoneNumberInterface;

class Blocklist implements BlocklistInterface
{
    /** @var UserManager */
    private $userManager;

    /** @var TwigSwiftMailer */
    private $mailer;

    /** @var BlockedPhoneNumberRepository */
    private $blockedPhoneRepository;

    /** @var PersonRepository */
    private $personRepository;

    /** @var EntityManagerInterface */
    private $em;

    /** @var BlocklistOptions */
    private $options;

    public function isBlocked(PhoneNumber $phoneNumber): bool
    {
        return $this->isManuallyBlocked($phoneNumber) || $this->isBlockedAutomatically($phoneNumber);
    }

    public function blockByPhone(PhoneNumber $phoneNumber): array
    {
        $blockedUsers = $this->userManager->blockUsersByPhone($phoneNumber);
        $this->notifyBlockedUsers($blockedUsers);

        return $blockedUsers;
    }

    public function addBlockedPhoneNumber(
        PhoneNumber $phoneNumber,
        PersonInterface $blockedBy
    ): BlockedPhoneNumberInterface {
        $blockedPhoneNumber = new BlockedPhoneNumber($phoneNumber, $blockedBy, new \DateTime());
        $this->em->persist($blockedPhoneNumber);
        $this->em->flush();

        return $blockedPhoneNumber;
    }

    /**
     * @param PersonInterface[] $blockedUsers
     */
    private function notifyBlockedUsers(array $blockedUsers)
    {
        foreach ($blockedUsers as $person) {
            $this->mailer->sendAccountBlockedMessage($person);
        }
    }

    private function isManuallyBlocked(PhoneNumber $phoneNumber): bool
    {
        return $this->blockedPhoneRepository->findByPhone($phoneNumber) instanceof PhoneNumber;
    }

    private function isBlockedAutomatically(PhoneNumber $phoneNumber): bool
    {
        if ($this->options->isAutoBlockEnabled()) {
            $autoBlockLimit = $this->options->getAutoBlockPhoneLimit();

            // TODO: count ONLY verified phones!!!!!
            return $this->personRepository->countByPhone($phoneNumber) >= $autoBlockLimit;
        }

        return false;
    }
}