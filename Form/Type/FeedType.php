<?php

namespace Newscoop\IngestPluginBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class FeedType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $publicationSettingsArray = array(
            'class' => 'Newscoop\Entity\Publication',
            'property' => 'name',
            'multiple' => false,
            'expanded' => false,
            'label' => 'plugin.ingest.feeds.publication',
            'attr' => array('class' => 'publication'),
        );

        // Only display empty value on add
        if ($options['type'] == 'add')  {
            $publicationSettingsArray['empty_value'] = 'plugin.ingest.feeds.choose_publication';
        }

        $builder
            ->add('enabled', 'checkbox', array(
                'label' => 'plugin.ingest.feeds.enabled',
                'required' => false,
            ))
            ->add('name', 'text', array(
                'label' => 'plugin.ingest.feeds.name',
            ))
            ->add('url', 'url', array(
                'label' => 'plugin.ingest.feeds.url',
                'required' => false,
            ))
            ->add('mode', 'choice', array(
                'choices' => array('auto' => 'plugin.ingest.feeds.mode.auto', 'manual' => 'plugin.ingest.feeds.mode.manual'),
                'label' => 'plugin.ingest.feeds.mode.name',
                'required' => true,
            ))
            ->add('publication', 'entity', $publicationSettingsArray);

        $formModifier = function(FormInterface $form, \Newscoop\Entity\Publication $publication=null) {
            if ($publication === null) return false;

            $sections = $publication->getSections();

            $form
                ->add('sections', 'entity', array(
                    'choices' => $sections,
                    'class' => 'Newscoop\Entity\Section',
                    'multiple' => true,
                    'expanded' => true,
                    'label' => 'plugin.ingest.feeds.section',
                ));
        };

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function(FormEvent $event) use ($formModifier) {
                // this would be your entity, i.e. SportMeetup
                $data = $event->getData();

                $formModifier($event->getForm(), $data->getPublication());
            }
        );

        $builder->get('publication')->addEventListener(
            FormEvents::POST_SUBMIT,
            function(FormEvent $event) use ($formModifier) {
                // It's important here to fetch $event->getForm()->getData(), as
                // $event->getData() will get you the client data (that is, the ID)
                $publication = $event->getForm()->getData();

                // since we've added the listener to the child, we'll have to pass on
                // the parent to the callback functions!
                $formModifier($event->getForm()->getParent(), $publication);
            }
        );

        $builder
            ->add('parser', 'entity', array(
                'class' => 'Newscoop\IngestPluginBundle\Entity\Parser',
                'property' => 'name',
            ))
            ->add('save', 'submit', array(
                'label' => 'plugin.ingest.feeds.form.button.' . ($options['type'] == 'add' ? 'add' : 'save'),
            ))
            ->add('cancel', 'button', array(
                'label' => 'plugin.ingest.feeds.form.button.cancel',
            ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Newscoop\IngestPluginBundle\Entity\Feed',
            'type' => 'add'
        ));
    }

    public function getName()
    {
        return 'feed';
    }
}
