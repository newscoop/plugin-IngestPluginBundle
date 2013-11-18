<?php

namespace Newscoop\IngestPluginBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class FeedType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('url')
            ->add('mode', 'choice', array(
                'choices' => array('auto' => 'plugin.ingest.feeds.mode.auto', 'manual' => 'plugin.ingest.feeds.mode.manual'),
                'required' => true,
            ))
            ->add('sections', 'entity', array(
                'class' => 'Newscoop\Entity\Section',
                'property' => 'name',
                'multiple' => true,
                'expanded' => true,
            ))
            ->add('parser', 'entity', array(
                'class' => 'Newscoop\IngestPluginBundle\Entity\Parser',
                'property' => 'name',
            ))
            ->add('save', 'submit')
            ->add('cancel', 'button');
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Newscoop\IngestPluginBundle\Entity\Feed',
        ));
    }

    public function getName()
    {
        return 'feed';
    }
}
