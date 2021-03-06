<?php
/**
 * This file is part of the login-cidadao project or it's bundles.
 *
 * (c) Guilherme Donato <guilhermednt on github>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LoginCidadao\SupportBundle\Controller;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\QueryBuilder;
use LoginCidadao\APIBundle\Security\Audit\ActionLogger;
use LoginCidadao\CoreBundle\Entity\PersonRepository;
use LoginCidadao\CoreBundle\Entity\SentEmail;
use LoginCidadao\CoreBundle\Helper\GridHelper;
use LoginCidadao\CoreBundle\Model\PersonInterface;
use LoginCidadao\SupportBundle\Form\PersonSearchFormType;
use LoginCidadao\SupportBundle\Model\PersonSearchRequest;
use LoginCidadao\SupportBundle\Service\SupportHandler;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class PersonSupportController
 * @package LoginCidadao\SupportBundle\Controller
 *
 * @Security("has_role('ROLE_SUPPORT_AGENT')")
 * @codeCoverageIgnore
 */
class PersonSupportController extends Controller
{
    /**
     * @Route("/support/search", name="lc_support_person_search")
     * @Security("has_role('ROLE_SUPPORT_SEARCH_USERS')")
     * @param Request $request
     * @return Response
     */
    public function searchAction(Request $request)
    {
        $gridView = null;
        $search = new PersonSearchRequest();

        $search->smartSearch = $request->get('search', null);

        $form = $this->createForm(PersonSearchFormType::class, $search);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var PersonRepository $repo */
            $repo = $this->getDoctrine()->getRepository('LoginCidadaoCoreBundle:Person');
            $query = $repo->getSmartSearchQuery($search->smartSearch);
            try {
                $person = $query->getQuery()->getOneOrNullResult();

                if ($person instanceof PersonInterface) {
                    return $this->redirectToRoute('lc_support_person_view', [
                        'id' => $person->getId(),
                        'ticket' => $search->supportTicket,
                    ]);
                }
            } catch (NonUniqueResultException $e) {
                $grid = $this->getPersonGrid($query, $form);
                $gridView = $grid->createView($request);
            }
        }

        return $this->render('LoginCidadaoSupportBundle:PersonSupport:index.html.twig', [
            'form' => $form->createView(),
            'grid' => $gridView,
            'search' => $search,
        ]);
    }

    /**
     * @Route("/support/person/{id}", name="lc_support_person_view")
     * @param Request $request
     * @param $id
     * @return Response
     */
    public function viewAction(Request $request, $id)
    {
        try {
            $supportRequest = $this->validateSupportTicketId($request->get('ticket'));
        } catch (NotFoundHttpException $e) {
            if (!$this->isGranted('ROLE_SKIP_SUPPORT_TOKEN_VALIDATION')) {
                throw $e;
            }
            $supportRequest = null;
        }

        /** @var SupportHandler $supportHandler */
        $supportHandler = $this->get(SupportHandler::class);

        $person = $supportHandler->getSupportPerson($id);
        $phoneMetadata = $supportHandler->getPhoneMetadata($person);
        $thirdPartyConnections = $supportHandler->getThirdPartyConnections($person);

        /** @var ActionLogger $actionLogger */
        $actionLogger = $this->get('lc.action_logger');
        $actionLogger->registerProfileView($request, $person, $this->getUser(), [$this, 'viewAction']);

        return $this->render('LoginCidadaoSupportBundle:PersonSupport:view.html.twig', [
            'person' => $person,
            'thirdPartyConnections' => $thirdPartyConnections,
            'supportRequest' => $supportRequest,
            'dataValidation' => $supportHandler->getValidationMap($person),
            'phoneMetadata' => $phoneMetadata,
        ]);
    }

    private function validateSupportTicketId(string $ticket = null): SentEmail
    {
        /** @var SupportHandler $supportHandler */
        $supportHandler = $this->get(SupportHandler::class);
        $sentEmail = $ticket ? $supportHandler->getInitialMessage($ticket) : null;
        if (!$sentEmail instanceof SentEmail) {
            throw $this->createNotFoundException("Invalid Support Ticket ID");
        }

        return $sentEmail;
    }

    private function getPersonGrid(QueryBuilder $query, $form): GridHelper
    {
        $grid = new GridHelper();
        $grid->setId('person-grid');
        $grid->setPerPage(5);
        $grid->setMaxResult(5);
        $grid->setInfiniteGrid(true);
        $grid->setRoute('lc_support_person_search');
        $grid->setRouteParams([$form->getName()]);
        $grid->setQueryBuilder($query);

        return $grid;
    }
}
