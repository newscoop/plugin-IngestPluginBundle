<?php
/**
 * @package   Newscoop\IngestPluginBundle
 * @author    Mischa Gorinskat <mischa.gorinskat@sourcefabric.org>
 * @copyright 2015 Sourcefabric o.p.s.
 * @license   http://www.gnu.org/licenses/gpl-3.0.txt
 */

namespace Newscoop\IngestPluginBundle\Form\EventListener;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AddPublicationFieldSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(FormEvents::PRE_SET_DATA => 'preSetData', FormEvents::PRE_SUBMIT => 'preSubmit');
    }

    public function addField(Form $form, $language = null)
    {
        $options = $form->getConfig()->getOptions();

        $publicationSettingsArray = array(
            'class' => 'Newscoop\Entity\Publication',
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

        if ($language !== null) {
            $publicationSettingsArray['query_builder'] = function (EntityRepository $er) use ($language) {
                return $er->createQueryBuilder('p')
                        ->select('p')
                        ->from('\Newscoop\Entity\Issue', 'i')
                        ->where('i.publication = p')
                        ->andWhere('i.language = :language')
                        ->setParameter('language', $language);
            };
        }

        $form->add('publication', 'entity', $publicationSettingsArray);
    }

    public function preSetData(FormEvent $event)
    {
        $data = $event->getData();
        $this->addField($event->getForm(), $data ? $data->getLanguage() : null);
    }

    public function preSubmit(FormEvent $event)
    {
        $data = $event->getData();
        $language = null;
        if (is_array($data) && array_key_exists('language', $data) && $data['language']) {
            $language = (int) $data['language'];
        }
        $this->addField($event->getForm(), $language);
    }
}
