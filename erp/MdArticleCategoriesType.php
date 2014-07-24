<?php

namespace Admin\MasterDataBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class MdArticleCategoriesType extends AbstractType
{
        /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', 'text', array('error_bubbling'=>true))
            ->add('description')
            ->add('url')
            ->add('level')
            ->add('leftId')
            ->add('rightId')
            ->add('positionNo')
            ->add('deleted')
            ->add('shortName', 'text', array('error_bubbling'=>true))
            ->add('margin', 'text', array('error_bubbling'=>true))
            ->add('transportMargin', 'text', array('error_bubbling'=>true))
            ->add('status', 'choice', array('choices'=>array(0=>'inactiv', 1=>'activ')))
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Admin\MasterDataBundle\Entity\MdArticleCategories'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'form_categories';
    }
}
