<?php

namespace IdCultura\IdCulturaBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use LoginCidadao\CoreBundle\Form\Type\RegistrationFormType as BaseType;
use Symfony\Component\HttpFoundation\Session\Session;

class RegistrationFormType extends BaseType
{
    /**
     * @param string $class The User class name
     * @param Session $session
     */
    public function __construct($class, Session $session)
    {
        parent::__construct($class, $session);
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add(
                'firstName',
                null,
                [
                    'required' => true,
                    'label' => 'person.form.firstName.label',
                    'attr' => [
                        'placeholder' => 'person.form.firstName.placeholder',
                    ],
                ]
            )
            ->add(
                'surname',
                null,
                [
                    'required' => true,
                    'attr' => [
                        'placeholder' => 'person.form.surname.placeholder',
                    ],
                ]
            );
    }
}
