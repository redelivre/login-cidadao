<?php

namespace LoginCidadao\CoreBundle\EventListener;

use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Event\FormEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use FOS\UserBundle\Util\TokenGeneratorInterface;
use FOS\UserBundle\Mailer\MailerInterface;
use FOS\UserBundle\Event\GetResponseUserEvent;
use FOS\UserBundle\Event\FilterUserResponseEvent;
use LoginCidadao\NotificationBundle\Helper\NotificationsHelper;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Translation\TranslatorInterface;
use LoginCidadao\ValidationBundle\Validator\Constraints\UsernameValidator;
use Doctrine\ORM\EntityManager;
use LoginCidadao\CoreBundle\Exception\LcEmailException;
use LoginCidadao\CoreBundle\Entity\Authorization;

class RegisterListner implements EventSubscriberInterface
{
    private $router;

    /** \Symfony\Component\HttpFoundation\Session\Session * */
    private $session;
    private $translator;
    private $mailer;
    private $tokenGenerator;

    /** @var NotificationsHelper */
    private $notificationsHelper;
    private $emailUnconfirmedTime;
    protected $em;
    private $lcSupportedScopes;
    private $notificationHandler;

    public function __construct(UrlGeneratorInterface $router,
                                SessionInterface $session,
                                TranslatorInterface $translator,
                                MailerInterface $mailer,
                                TokenGeneratorInterface $tokenGenerator,
                                NotificationsHelper $notificationsHelper,
                                $emailUnconfirmedTime, $lcSupportedScopes,
                                $notificationHandler)
    {
        $this->router               = $router;
        $this->session              = $session;
        $this->translator           = $translator;
        $this->mailer               = $mailer;
        $this->tokenGenerator       = $tokenGenerator;
        $this->notificationsHelper  = $notificationsHelper;
        $this->emailUnconfirmedTime = $emailUnconfirmedTime;
        $this->lcSupportedScopes    = $lcSupportedScopes;
        $this->notificationHandler  = $notificationHandler;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            FOSUserEvents::REGISTRATION_SUCCESS => 'onRegistrationSuccess',
            FOSUserEvents::REGISTRATION_COMPLETED => 'onRegistrationCompleted',
            FOSUserEvents::REGISTRATION_CONFIRM => 'onEmailConfirmed'
        );
    }

    public function onRegistrationSuccess(FormEvent $event)
    {
        $user = $event->getForm()->getData();

        if (null === $user->getConfirmationToken()) {
            $user->setConfirmationToken($this->tokenGenerator->generateToken());
            $user->setEmailExpiration(new \DateTime("+$this->emailUnconfirmedTime"));
        }
        if ($this->em->getRepository('LoginCidadaoCoreBundle:Person')->findOneBy(array(
                'emailCanonical' =>
                $user->getEmailCanonical()
            ))) {
            throw new LcEmailException('registration.email.registered');
        }

        $key = '_security.main.target_path';
        if ($this->session->has($key)) {
            //this is to be catch by loggedinUserListener.php
            return $event->setResponse(new RedirectResponse($this->router->generate('lc_home')));
        }

        $email    = explode('@', $user->getEmailCanonical(), 2);
        $username = $email[0];
        if (!UsernameValidator::isUsernameValid($username)) {
            $url = $this->router->generate('lc_update_username');
        } else {
            $url = $this->router->generate('fos_user_profile_edit');
        }
        $event->setResponse(new RedirectResponse($url));
    }

    public function onRegistrationCompleted(FilterUserResponseEvent $event)
    {
        $user = $event->getUser();
        $auth = new Authorization();
        $auth->setPerson($user);
        $auth->setClient($this->notificationHandler->getLoginCidadaoClient());
        $auth->setScope(explode(' ', $this->lcSupportedScopes));
        $this->em->persist($auth);
        $this->em->flush();

        $this->mailer->sendConfirmationEmailMessage($user);

        if (strlen($user->getPassword()) == 0) {
            $this->notificationsHelper->enforceEmptyPasswordNotification($user);
        }
    }

    public function onEmailConfirmed(GetResponseUserEvent $event)
    {
        $event->getUser()->setEmailConfirmedAt(new \DateTime());
        $event->getUser()->setEmailExpiration(null);

        $this->session->getFlashBag()->add('success',
            $this->translator->trans('registration.confirmed',
                array(
                '%username%' => $event->getUser()->getFirstName()
                ), 'FOSUserBundle'));
        $this->session->getFlashBag()->get('alert.unconfirmed.email');

        $url = $this->router->generate('fos_user_profile_edit');
        $event->setResponse(new RedirectResponse($url));
    }

    public function setEntityManager(EntityManager $var)
    {
        $this->em = $var;
    }
}
