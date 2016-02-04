<?php

namespace LoginCidadao\NotificationBundle\Entity;

use Doctrine\ORM\EntityRepository;
use LoginCidadao\CoreBundle\Entity\Person;
use Doctrine\ORM\Query;
use LoginCidadao\CoreBundle\Model\PersonInterface;
use LoginCidadao\OAuthBundle\Model\ClientInterface;

class NotificationRepository extends EntityRepository
{

    public function findNextNotifications(Person $person, $items = 8,
                                          $lastId = 0,
                                          ClientInterface $client = null)
    {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('n')
            ->from('LoginCidadaoNotificationBundle:Notification', 'n')
            ->where('n.person = :person')
            ->orderBy('n.id', 'DESC')
            ->setMaxResults($items)
            ->setParameter('person', $person);

        if ($lastId > 0) {
            $qb->andWhere('n.id < :lastId')
                ->setParameter('lastId', $lastId);
        }

        if ($client instanceof ClientInterface) {
            $qb
                ->andWhere('n.sender = :client')
                ->setParameter('client', $client);
        }

        return $qb->getQuery()->getResult();
    }

    public function findAllUnread(Person $person, $level = null)
    {
        if (is_null($level)) {
            return $this->getEntityManager()
                    ->createQuery('SELECT n FROM LoginCidadaoNotificationBundle:Notification n WHERE n.person = :person AND n.dateRead is null')
                    ->setParameter('person', $person)
                    ->getResult();
        } else {
            return $this->getEntityManager()
                    ->createQuery('SELECT n FROM LoginCidadaoNotificationBundle:Notification n WHERE n.person = :person AND n.dateRead is null AND n.level = :level')
                    ->setParameter('person', $person)
                    ->setParameter('level', $level)
                    ->getResult();
        }
    }

    public function getTotalUnreadGroupByClient($person)
    {
        $qb = $this->getEntityManager()->createQueryBuilder('n')
            ->select('c')
            ->from('LoginCidadaoNotificationBundle:Notification', 'n')
            ->join('LoginCidadaoOAuthBundle:Client', 'c', 'WITH', 'n.sender = c')
            ->where('n.person = :person')
            ->andWhere('n.readDate IS NULL')
            ->setParameter('person', $person)
            ->groupBy('c.id', 'c.name')
            ->orderBy('c.id', 'ASC');

        return $qb
                ->getQuery()
                ->getResult();
    }

    public function getTotalUnread($person)
    {
        return $this->getEntityManager()->createQueryBuilder('n')
                ->select('COUNT(n.id) total')
                ->from('LoginCidadaoNotificationBundle:Notification',
                       'n')
                ->where('n.person = :person')
                ->andWhere('n.readDate IS NULL')
                ->setParameter('person', $person)
                ->getQuery()->getSingleScalarResult();
    }

    public function findUnreadUpToLevel(Person $person, $maxLevel = null)
    {
        if (is_null($maxLevel)) {
            return $this->findAllUnread($person);
        } else {
            return $this->getEntityManager()
                    ->createQuery('SELECT n FROM LoginCidadaoNotificationBundle:Notification n WHERE n.person = :person AND n.readDate is null AND n.level <= :level')
                    ->setParameter('person', $person)
                    ->setParameter('level', $maxLevel)
                    ->getResult();
        }
    }

    public function findUntil(PersonInterface $person, $start, $end)
    {
        $qb = $this->createQueryBuilder('n');
        $qb->where($qb->expr()->between('n.id', ':start', ':end'))
            ->setParameters(compact('start', 'end'));

        return $qb->getQuery()->getResult();
    }

    public function getUnread(Person $person, $limit = 5)
    {
        return $this->getEntityManager()
                ->createQuery('SELECT n FROM LoginCidadaoNotificationBundle:Notification n WHERE n.person = :person AND n.readDate is null')
                ->setParameter('person', $person)
                ->setMaxResults($limit)
                ->getResult();

    }

}
