<?php

namespace LoginCidadao\CoreBundle\Mailer;

use FOS\UserBundle\Mailer\TwigSwiftMailer as BaseMailer;
use FOS\UserBundle\Model\UserInterface;

class TwigSwiftMailer extends BaseMailer
{
    protected $mailerSenderMail;

    public function setMailerSenderMail($var)
    {
        $this->mailerSenderMail = $var;
    }

    public function sendEmailChangedMessage(UserInterface $user, $oldEmail)
    {
        $template = $this->parameters['template']['email_changed'];
        $fromEmail = $this->parameters['from_email']['email_changed'];
        $fromName = $this->parameters['from_email']['email_sender_name'];
        $from = array($fromEmail => $fromName);

        $context = array(
            'user' => $user,
            'oldEmail' => $oldEmail
        );

        $this->sendMessage($template, $context, $from, $oldEmail);
    }

    public function sendEmailBasedOnNotification($notificationId, $fromEmailName, $toEmail, $subject, $htmlBody)
    {
        $templateName = $this->parameters['template']['notification_email'];
        $context = array(
            'subject' => $subject,
            'body' => $htmlBody,
            'id' => $notificationId
        );
        $fromEmail = $this->parameters['from_email']['email_changed'];
        $from = array($fromEmail => $fromEmailName);
        $this->sendMessage($templateName, $context, $from, $toEmail);
        return;

        $message = \Swift_Message::newInstance()
        ->setSubject($subject)
        ->setFrom(array($this->parameters['from_email']['email_changed'] => $fromEmailName))
        ->setTo($toEmail);
            $message->setBody($htmlBody, 'text/html')
            ->addPart($htmlBody, 'text/plain');
        $this->mailer->send($message);
    }
}
