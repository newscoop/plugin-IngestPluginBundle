<?php
/**
 * @package   Newscoop\IngestPluginBundle
 * @author    Mischa Gorinskat <mischa.gorinskat@sourcefabric.org>
 * @copyright 2013 Sourcefabric o.p.s.
 * @license   http://www.gnu.org/licenses/gpl-3.0.txt
 */

namespace Newscoop\IngestPluginBundle\Form\Type;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Newscoop\Entity\Publication;
use Newscoop\IngestPluginBundle\Form\EventListener\AddPublicationFieldSubscriber;
use Newscoop\IngestPluginBundle\Form\EventListener\AddSectionFieldSubscriber;
use Newscoop\IngestPluginBundle\Form\EventListener\AddIssueFieldSubscriber;
use Newscoop\IngestPluginBundle\Form\EventListener\AddTopicFieldSubscriber;

class FeedType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('enabled', 'checkbox', array(
                'label' => 'plugin.ingest.feeds.enabled',
                'required' => false,
            ))
            ->add('name', 'text', array(
                'label' => 'plugin.ingest.feeds.name',
            ))
            ->add('parser', 'entity', array(
                'label' => 'plugin.ingest.feeds.parser',
                'class' => 'Newscoop\IngestPluginBundle\Entity\Parser',
                'property' => 'name',
                'query_builder' => function(EntityRepository $er) {
                    return $er->getParsers(true, true);
                }
            ))
            ->add('url', 'url', array(
                'label' => 'plugin.ingest.feeds.url',
                'required' => false,
                'attr' => array(
                    'help_text' => 'plugin.ingest.feeds.form.help.url'
                )
            ))
            ->add('mode', 'choice', array(
                'choices' => array('auto' => 'plugin.ingest.feeds.mode.auto', 'manual' => 'plugin.ingest.feeds.mode.manual'),
                'label' => 'plugin.ingest.feeds.mode.name',
                'required' => true,
                'attr' => array(
                    'help_text' => 'plugin.ingest.feeds.form.help.mode'
                )
            ))
            ->add('language', 'entity', array(
                'class' => 'Newscoop\Entity\Language',
                'property' => 'name',
                'multiple' => false,
                'expanded' => false,
                'required' => false,
                'label' => 'plugin.ingest.feeds.language',
                'empty_value' => 'plugin.ingest.feeds.language_autodetect',
                'empty_data' => null,
                'attr' => array('class' => 'auto-submit')
            ));

        $builder
            ->addEventSubscriber(new AddPublicationFieldSubscriber())
            ->addEventSubscriber(new AddIssueFieldSubscriber())
            ->addEventSubscriber(new AddSectionFieldSubscriber());

        $builder
            ->add('topics', 'topic_selector', array(
                'label' => 'plugin.ingest.feeds.topics',
                'attr' => array(
                    'help_text' => 'plugin.ingest.feeds.form.help.topics'
                )
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
        $resolver->setRequired(array(
            'em'
        ));

        $resolver->setDefaults(array(
            'data_class' => 'Newscoop\IngestPluginBundle\Entity\Feed',
            'type' => 'add',
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'intention' => 'ingest_feed_type_form'
        ));
    }

    public function getName()
    {
        return 'feed_type';
    }
}
