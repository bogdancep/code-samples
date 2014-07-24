<?php

namespace Admin\UserManagementBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class UmUsersType extends AbstractType
{
        /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email', 'text', array(
                'error_bubbling' => true))
            ->add('name', 'text', array(
                'error_bubbling' => true))
            ->add('active')
            ->add('passwordExpireDaysNo')
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Admin\UserManagementBundle\Entity\UmUsers'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'form_umusers';
    }
}
