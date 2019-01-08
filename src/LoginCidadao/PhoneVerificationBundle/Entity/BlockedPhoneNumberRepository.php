<?php
/**
 * This file is part of the login-cidadao project or it's bundles.
 *
 * (c) Guilherme Donato <guilhermednt on github>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LoginCidadao\PhoneVerificationBundle\Entity;

use Doctrine\ORM\EntityRepository;
use libphonenumber\PhoneNumber;

class BlockedPhoneNumberRepository extends EntityRepository
{
    /**
     * @param PhoneNumber $phoneNumber
     * @return BlockedPhoneNumber
     */
    public function findByPhone(PhoneNumber $phoneNumber): BlockedPhoneNumber
    {
        /** @var BlockedPhoneNumber $blockedPhone */
        $blockedPhone = $this->findOneBy(['phoneNumber' => $phoneNumber]);

        return $blockedPhone;
    }
}