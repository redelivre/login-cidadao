<?php
/**
 * This file is part of the login-cidadao project or it's bundles.
 *
 * (c) Guilherme Donato <guilhermednt on github>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LoginCidadao\RemoteClaimsBundle\Fetcher;

use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use League\Uri\Schemes\Http;
use LoginCidadao\OAuthBundle\Entity\ClientRepository;
use LoginCidadao\OAuthBundle\Model\ClientInterface;
use LoginCidadao\RemoteClaimsBundle\Entity\RemoteClaim;
use LoginCidadao\RemoteClaimsBundle\Entity\RemoteClaimRepository;
use LoginCidadao\RemoteClaimsBundle\Model\ClaimProviderInterface;
use LoginCidadao\RemoteClaimsBundle\Model\RemoteClaimFetcherInterface;
use LoginCidadao\RemoteClaimsBundle\Model\RemoteClaimInterface;
use LoginCidadao\RemoteClaimsBundle\Model\TagUri;
use LoginCidadao\RemoteClaimsBundle\Parser\RemoteClaimParser;
use LoginCidadao\OAuthBundle\Entity\Client as ClaimProvider;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RemoteClaimFetcher implements RemoteClaimFetcherInterface
{
    /** @var  Client */
    private $httpClient;

    /** @var RemoteClaimRepository */
    private $claimRepo;

    /** @var ClientRepository */
    private $clientRepo;

    /** @var EntityManagerInterface */
    private $em;

    /**
     * RemoteClaimFetcher constructor.
     * @param Client $httpClient
     * @param EntityManagerInterface $em
     * @param RemoteClaimRepository $claimRepository
     * @param ClientRepository $clientRepository
     */
    public function __construct(
        Client $httpClient,
        EntityManagerInterface $em,
        RemoteClaimRepository $claimRepository,
        ClientRepository $clientRepository
    ) {
        $this->em = $em;
        $this->httpClient = $httpClient;
        $this->claimRepo = $claimRepository;
        $this->clientRepo = $clientRepository;
    }

    public function fetchRemoteClaim($claimUri)
    {
        try {
            $uri = Http::createFromString($claimUri);
        } catch (\Exception $e) {
            $claimName = TagUri::createFromString($claimUri);
            $uri = $this->discoverClaimUri($claimName);
        }

        $response = $this->httpClient->get($uri);
        $body = $response->getBody()->__toString();

        $remoteClaim = RemoteClaimParser::parseClaim($body, new RemoteClaim(), new ClaimProvider());

        return $remoteClaim;
    }

    /**
     * @param TagUri $claimName
     * @return string
     */
    public function discoverClaimUri(TagUri $claimName)
    {
        $uri = Http::createFromComponents([
            'host' => $claimName->getAuthorityName(),
            'query' => http_build_query([
                'resource' => $claimName,
                'rel' => 'http://openid.net/specs/connect/1.0/claim',
            ]),
        ]);

        $response = $this->httpClient->get($uri);
        $json = json_decode($response->getBody());

        foreach ($json->links as $link) {
            if ($link->rel === 'http://openid.net/specs/connect/1.0/claim'
                && $json->subject === $claimName->__toString()) {
                return $link->href;
            }
        }

        throw new NotFoundHttpException("Couldn't find the Claim's URI");
    }

    /**
     * Fetches a RemoteClaimInterface via <code>fetchRemoteClaim</code>, persisting and returning the result.
     * @param TagUri|string $claimUri
     * @return RemoteClaimInterface
     */
    public function getRemoteClaim($claimUri)
    {
        $remoteClaim = $this->fetchRemoteClaim($claimUri);

        $provider = $this->getExistingClaimProvider($remoteClaim->getProvider());
        if ($provider instanceof ClaimProviderInterface) {
            $remoteClaim->setProvider($provider);
            $this->em->persist($provider);
        }

        $existingClaim = $this->claimRepo->findOneBy(['name' => $remoteClaim->getName()]);
        if ($existingClaim instanceof RemoteClaimInterface) {
            $remoteClaim = $existingClaim;
        }

        $this->em->persist($remoteClaim);
        $this->em->flush();

        return $remoteClaim;
    }

    /**
     * @param string[] $redirectUris
     * @return ClientInterface
     */
    private function findClaimProvider($redirectUris)
    {
        $clients = $this->clientRepo->findByRedirectUris($redirectUris);

        if (count($clients) > 1) {
            throw new \InvalidArgumentException('Ambiguous redirect_uris. More than one Relying Party found.');
        }

        return reset($clients);
    }

    private function getExistingClaimProvider(ClaimProviderInterface $provider = null)
    {
        if (!$provider instanceof ClaimProviderInterface) {
            return null;
        }

        $existingProvider = $this->findClaimProvider($provider->getRedirectUris());
        if ($existingProvider instanceof ClaimProviderInterface) {
            return $existingProvider;
        }

        return $provider;
    }
}