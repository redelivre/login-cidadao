<?php
/**
 * This file is part of the login-cidadao project or it's bundles.
 *
 * (c) Guilherme Donato <guilhermednt on github>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LoginCidadao\CoreBundle\Controller;

use Doctrine\Common\Collections\Collection;
use Symfony\Component\HttpFoundation\Request;
use LoginCidadao\CoreBundle\Entity\City;
use LoginCidadao\CoreBundle\Entity\PersonAddress;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class PersonAddressController extends Controller
{
    /**
     * @Route("/person/addresses", name="lc_person_addresses")
     * @Template()
     */
    public function listAction()
    {
        $deleteForms = $this->getDeleteForms();

        return compact('deleteForms');
    }

    /**
     * @Route("/person/addresses/new", name="lc_person_addresses_new")
     * @Template()
     */
    public function newAddressAction(Request $request)
    {
        $address = new PersonAddress();
        $formType = 'LoginCidadao\CoreBundle\Form\Type\PersonAddressFormType';
        $form = $this->createForm($formType, $address);

        $form->handleRequest($request);

        $em = $this->getDoctrine()->getManager();
        if ($form->isValid()) {
            $address->setPerson($this->getUser());
            $em->persist($address);
            $em->flush();

            return $this->redirect($this->generateUrl('lc_person_addresses'));
        }
        $deleteForms = $this->getDeleteForms();

        return compact('form', 'deleteForms');
    }

    /**
     * @Route("/person/addresses/{id}/edit", name="lc_person_addresses_edit")
     * @Template("LoginCidadaoCoreBundle:PersonAddress:newAddress.html.twig")
     */
    public function editAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $address = $this->findOr404($id);
        if ($address->getPerson()->getId() !== $this->getUser()->getId()) {
            throw new AccessDeniedException();
        }
        if ($address->getId() > 0) {
            $city = $address->getCity();
            if ($city instanceof City) {
                $state = $city->getState();
                $country = $state->getCountry();
                $address->getLocation()->setCity($city)
                    ->setState($state)->setCountry($country);
            }
        }

        $formType = 'LoginCidadao\CoreBundle\Form\Type\PersonAddressFormType';
        $form = $this->createForm($formType, $address);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $address->setPerson($this->getUser());

            $em->flush();

            return $this->redirect($this->generateUrl('lc_person_addresses'));
        }

        return [
            'edit_form' => $form->createView(),
            'deleteForms' => $this->getDeleteForms(),
        ];
    }

    /**
     * @Route("/person/addresses/{id}/remove", name="lc_person_addresses_delete")
     * @Template()
     */
    public function deleteAction(Request $request, $id)
    {
        $translator = $this->get('translator');
        $formType = 'LoginCidadao\CoreBundle\Form\Type\RemovePersonAddressFormType';
        $form = $this->createForm($formType);
        $form->handleRequest($request);

        if ($form->isValid()) {
            try {
                $address = $this->findOr404($id);
                $person = $this->getUser();
                if ($address->getPerson()->getId() !== $person->getId()) {
                    throw new AccessDeniedException();
                }
                $em = $this->getDoctrine()->getManager();
                $em->remove($address);
                $em->flush();
            } catch (AccessDeniedException $e) {
                $this->get('session')->getFlashBag()->add(
                    'error',
                    $translator->trans("Access Denied.")
                );
            } catch (\Exception $e) {
                $this->get('session')->getFlashBag()->add(
                    'error',
                    $translator->trans("Wasn't possible to remove this address.")
                );
                $this->get('session')->getFlashBag()->add(
                    'error',
                    $e->getMessage()
                );
            }
        } else {
            $this->get('session')->getFlashBag()->add(
                'error',
                $translator->trans("Wasn't possible to remove this address.")
            );
        }

        return $this->redirect($this->generateUrl('lc_person_addresses'));
    }

    private function getDeleteForms()
    {
        $person = $this->getUser();
        $deleteForms = array();
        $addresses = $person->getAddresses();

        if (is_array($addresses) || $addresses instanceof Collection) {
            foreach ($addresses as $address) {
                $data = array('address_id' => $address->getId());
                $deleteForms[$address->getId()] = $this->createForm(
                    'LoginCidadao\CoreBundle\Form\Type\RemovePersonAddressFormType',
                    $data
                )->createView();
            }
        }

        return $deleteForms;
    }

    private function findOr404($id)
    {
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('LoginCidadaoCoreBundle:PersonAddress');

        $address = $repo->find($id);
        if (!$address instanceof PersonAddress) {
            throw new NotFoundHttpException('PersonAddress not found');
        }

        return $address;
    }
}
