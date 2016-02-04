<?php

namespace LoginCidadao\ValidationControlBundle\Handler;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use LoginCidadao\ValidationControlBundle\ValidationEvents;
use LoginCidadao\ValidationControlBundle\Event\InstantiateIdCardEvent;
use LoginCidadao\CoreBundle\Entity\State;
use LoginCidadao\CoreBundle\Model\IdCardInterface;
use LoginCidadao\CoreBundle\Entity\IdCard;
use LoginCidadao\ValidationControlBundle\Validator\Constraints\IdCardValidator;
use Symfony\Component\Validator\Constraint;
use LoginCidadao\ValidationControlBundle\Event\IdCardValidateEvent;
use Symfony\Component\Validator\ExecutionContextInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;

class ValidationHandler
{

    /** @var EventDispatcherInterface */
    private $dispatcher;

    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param State $state
     * @return IdCardInterface
     */
    public function instantiateIdCard(State $state = null)
    {
        $event = new InstantiateIdCardEvent($state);
        $this->dispatcher->dispatch(ValidationEvents::VALIDATION_ID_CARD_INSTANTIATE,
                                    $event);
        $idCard = $event->getIdCard();
        if (!($idCard instanceof IdCardInterface)) {
            $idCard = new IdCard();
            if ($state instanceof State) {
                $idCard->setState($state);
            }
        }
        return $idCard;
    }

    public function idCardValidate(ExecutionContextInterface $validator,
                                   Constraint $constraint,
                                   IdCardInterface $idCard)
    {
        $event = new IdCardValidateEvent($validator, $constraint, $idCard);
        $this->dispatcher->dispatch(ValidationEvents::ID_CARD_VALIDATE, $event);
    }
    
    public function persistIdCard(FormInterface $form, $data)
    {
        $event = new FormEvent($form, $data);
        $this->dispatcher->dispatch(ValidationEvents::VALIDATION_ID_CARD_PERSIST, $event);
    }

}
