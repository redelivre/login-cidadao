<?php
/*
 * This file is part of the login-cidadao project or it's bundles.
 *
 * (c) Guilherme Donato <guilhermednt on github>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LoginCidadao\APIBundle\Transformer;

use Doctrine\ORM\EntityManager;
use LoginCidadao\CoreBundle\Model\PersonInterface;
use LoginCidadao\OAuthBundle\Model\ClientInterface;

class PersonTransformer
{
    /** @var LoginCidadao\OAuthBundle\Model\ClientInterface */
    private $client;

    /** @var EntityManager */
    private $em;

    public function __construct(EntityManager $em, ClientInterface $client)
    {
        $this->em     = $em;
        $this->client = $client;
    }

    public function getSubjectIdentifier(PersonInterface $person)
    {
        $id       = $person->getId();
        $metadata = $this->client->getMetadata();

        if ($metadata === null || $metadata->getSubjectType() === 'public') {
            return $id;
        }

        if ($metadata->getSubjectType() === 'pairwise') {
            $sectorIdentifier = $metadata->getSectorIdentifier();

            $salt     = $this->pairwiseSubjectIdSalt;
            $pairwise = hash('sha256', $sectorIdentifier.$id.$salt);
            return $pairwise;
        }
    }

    public function getPerson($accessToken)
    {
        $repo = $this->em->getRepository('LoginCidadaoOAuthBundle:AccessToken');
    }
}
