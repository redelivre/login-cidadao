<?php

namespace LoginCidadao\CoreBundle\Entity;

use Doctrine\ORM\EntityRepository;

class CityRepository extends EntityRepository
{

    public function findByString($string, $countryId = null, $stateId = null)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb
            ->select('c')
            ->from('LoginCidadaoCoreBundle:City', 'c')
            ->join('LoginCidadaoCoreBundle:State', 's', 'WITH',
                   'c.state = s')
            ->join('LoginCidadaoCoreBundle:Country', 'co', 'WITH',
                   's.country = co')
            ->where('c.name LIKE :string OR LOWER(c.name) LIKE :string')
            ->addOrderBy('s.preference', 'DESC')
            ->addOrderBy('c.name', 'ASC')
            ->setParameter('string', "$string%");

        if ($stateId > 0) {
            $qb->andWhere('s.id = :stateId')
                ->setParameter('stateId', $stateId);
        }
        if ($countryId > 0) {
            $qb->andWhere('co.id = :countryId')
                ->setParameter('countryId', $countryId);
        }

        return $qb->getQuery()->getResult();
    }

    public function findByPreferedState()
    {
        $em = $this->getEntityManager();
        $states = $em->getRepository('LoginCidadaoCoreBundle:State')
                ->createQueryBuilder('s')
                ->orderBy('s.preference', 'DESC')
                ->setMaxResults(1)
                ->getQuery()->getResult();
        $state = reset($states);
        $cities = $state->getCities();
        return $cities;
    }

}
