<?php

namespace LoginCidadao\CoreBundle\EventListener;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\EntityManager;
use LoginCidadao\CoreBundle\Entity\CityRepository;
use LoginCidadao\OAuthBundle\Entity\Client;
use LoginCidadao\OAuthBundle\Entity\ClientRepository;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class CheckDeployEventSubscriber implements EventSubscriberInterface, LoggerAwareInterface
{
    const CHECK_DEPLOY_KEY = 'check_deploy';

    /** @var CacheProvider */
    private $cache;

    /** @var CityRepository */
    private $cityRepository;

    /** @var ClientRepository */
    private $clientRepository;

    /** @var string */
    private $defaultClientUid;

    /** @var string */
    private $requiredChannel;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        EntityManager $em,
        $defaultClientUid,
        $requiredChannel
    ) {
        $this->cityRepository = $em->getRepository('LoginCidadaoCoreBundle:City');
        $this->clientRepository = $em->getRepository('LoginCidadaoOAuthBundle:Client');
        $this->defaultClientUid = $defaultClientUid;
        $this->requiredChannel = $requiredChannel;

        $this->cache = null;
    }

    /**
     * @param CacheProvider $cache
     */
    public function setCacheProvider(CacheProvider $cache = null)
    {
        $this->cache = $cache;
    }

    /**
     * Sets a logger instance on the object
     *
     * @param LoggerInterface $logger
     * @return null
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::REQUEST => 'onRequest',
        );
    }

    public function onRequest(GetResponseEvent $event)
    {
        $this->checkRequiredChannel();
        $this->checkDeploy();
    }

    private function checkDeploy()
    {
        if (null === $this->cache || $this->cache->contains(self::CHECK_DEPLOY_KEY)) {
            return;
        }

        $clients = $this->clientRepository->countClients();
        $cities = $this->cityRepository->countCities();
        $hasDefaultClient = $this->clientRepository->findOneByUid($this->defaultClientUid) instanceof Client;

        if ($clients <= 0 || $cities <= 0 || !$hasDefaultClient) {
            $this->cache->delete(self::CHECK_DEPLOY_KEY);
            throw new \RuntimeException('Make sure you did run the populate database command.');
        } else {
            $this->cache->save(self::CHECK_DEPLOY_KEY, true);
        }
    }

    private function checkRequiredChannel()
    {
        if ($this->requiredChannel === 'https') {
            return;
        }

        $message = "The parameter 'required_channel' should be set to 'https'! This is a CRITICAL SECURITY issue. Ignore this message only if you are _ABSOLUTELY_ sure you know what you are doing.";
        if ($this->logger !== null) {
            $this->logger->critical($message);
        } else {
            throw new \RuntimeException($message);
        }
    }
}
