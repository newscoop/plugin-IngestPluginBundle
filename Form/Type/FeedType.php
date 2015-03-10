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
use Newscoop\IngestPluginBundle\Form\EventListener\AddUrlFieldSubscriber;

class FeedType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $factory = $builder->getFormFactory();

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
                'empty_value' => 'plugin.ingest.feeds.choose_parser',
                'class' => 'Newscoop\IngestPluginBundle\Entity\Parser',
                'property' => 'name',
                'query_builder' => function(EntityRepository $er) {
                    return $er->getParsers(true, true);
                },
                'attr' => array('class' => 'auto-submit')
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
            ->addEventSubscriber(new AddUrlFieldSubscriber());

        $builder
            ->add('topics', 'topic_selecter', array(
                'label' => 'plugin.ingest.feeds.topics',
                'attr' => array(
                    'class' => 'enable-select2',
                    'help_text' => 'plugin.ingest.feeds.form.help.topic'
                )
            ))
            ->add('save', 'submit', array(
                'label' => 'plugin.ingest.feeds.form.button.' . ($options['type'] == 'add' ? 'add' : 'save'),
            ))
            ->add('cancel', 'button', array(
                'label' => 'plugin.ingest.feeds.form.button.cancel',
            ));

        $addSectionField = function (FormEvent $event) {

            $form = $event->getForm()->getParent();
            $options = $form->getConfig()->getOptions();
            $issue = $event->getData();
            $publication = $form->get('publication')->getData();
            $em = $options['em'];

            if ($publication === null) {
                return false;
            }

            if ($issue === null) {
                $em = $options['em'];
                $issueResult = $em->getRepository('Newscoop\Entity\Issue')
                    ->getLatestByPublication($publication, 1);
                if (!$issueResult) {
                    return false;
                }
                try {
                    $issue = $issueResult->getSingleResult();
                    $issue = $issue->getId();
                } catch(\Doctrine\ORM\NoResultException $e) {
                    return false;
                }
            }

           $form
                ->add('sections', 'section_selecter', array(
                    'label' => 'plugin.ingest.feeds.sections',
                    'multiple' => true,
                    'attr' => array(
                        'class' => 'enable-select2',
                        'help_text' => 'plugin.ingest.feeds.form.help.section'
                    )
                ));
        };

        $addIssueField = function (FormEvent $event) use ($factory, $addSectionField) {

            $form = $event->getForm()->getParent();
            $options = $form->getConfig()->getOptions();
            $publication = $event->getData();

            if ($publication === null || !$publication || $event->getForm()->getNormData() === null) {
                return false;
            }

            $fieldBuilder = $factory->createNamedBuilder(
                'issue',
                'issue_selecter',
                null,
                array(
                    'label' => 'plugin.ingest.feeds.issue',
                    'auto_initialize' => false,
                    'multiple' => false,
                    'attr' => array(
                        'class' => 'enable-select2',
                        'help_text' => 'plugin.ingest.feeds.form.help.issue'
                    )
                )
            );

            $fieldBuilder->addEventListener(FormEvents::POST_SET_DATA, $addSectionField);
            $fieldBuilder->addEventListener(FormEvents::POST_SUBMIT, $addSectionField);

            $form->add($fieldBuilder->getForm());
        };

        $addPublicationField = function (FormEvent $event) use ($factory, $addIssueField) {

            $form = $event->getForm()->getParent();
            $options = $form->getConfig()->getOptions();
            $language = $event->getData();

            // Publications
            $publicationSettingsArray = array(
                'class' => 'Newscoop\Entity\Publication',
                'auto_initialize' => false,
                'property' => 'name',
                'multiple' => false,
                'expanded' => false,
                'label' => 'plugin.ingest.feeds.publication',
                'attr' => array(
                    'class' => 'auto-submit',
                    'help_text' => 'plugin.ingest.feeds.form.help.publication'
                )
            );

            // Only display empty value on add
            if ($options['type'] == 'add')  {
                $publicationSettingsArray['empty_value'] = 'plugin.ingest.feeds.choose_publication';
            }

            if ($language !== null && $language) {
                $publicationSettingsArray['query_builder'] = function (EntityRepository $er) use ($language) {
                    return $er->createQueryBuilder('p')
                            ->select('p')
                            ->from('\Newscoop\Entity\Issue', 'i')
                            ->where('i.publication = p')
                            ->andWhere('i.language = :language')
                            ->setParameter('language', $language);
                };
            }

            $fieldBuilder = $factory->createNamedBuilder(
                'publication',
                'entity',
                null,
                $publicationSettingsArray
            );

            $fieldBuilder->addEventListener(FormEvents::POST_SET_DATA, $addIssueField);
            $fieldBuilder->addEventListener(FormEvents::POST_SUBMIT, $addIssueField);

            $form->add($fieldBuilder->getForm());
        };

        $builder->get('language')->addEventListener(FormEvents::POST_SET_DATA, $addPublicationField);
        $builder->get('language')->addEventListener(FormEvents::POST_SUBMIT, $addPublicationField);
    }

    private function AddPublicationField() {

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
