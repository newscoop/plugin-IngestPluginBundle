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

class AddIssueFieldSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(FormEvents::PRE_SET_DATA => 'preSetData', FormEvents::PRE_SUBMIT => 'preSubmit');
    }

    public function addField(Form $form, $publication = null, $language = null)
    {
        if ($publication === null) {
            return false;
        }

        $options = $form->getConfig()->getOptions();

        $form->add('issue', 'entity', array(
            'class' => 'Newscoop\Entity\Issue',
            'property' => 'name',
            'multiple' => false,
            'expanded' => false,
            'required' =>  false,
            'label' => 'plugin.ingest.feeds.issue',
            'empty_value' => 'plugin.ingest.feeds.issue_latest',
            'empty_data' => null,
            'query_builder' => function (EntityRepository $er) use ($publication, $language) {
                $qb = $er->createQueryBuilder('i')
                    ->where('i.publication = :publication')
                    ->setParameter('publication', $publication);

                if ($language) {
                    $qb->andWhere('i.language = :language')
                        ->setParameter('language', $language);
                }

                return $qb;
            },
            'attr' => array(
                'class' => 'auto-submit',
                'help_text' => 'plugin.ingest.feeds.form.help.issue'
            )
        ));
    }

    public function preSetData(FormEvent $event)
    {
        $data = $event->getData();
        $publication = $data ? $data->getPublication() : null;
        $language = $data ? $data->getLanguage() : null;
        $this->addField($event->getForm(), $publication, $language);
    }

    public function preSubmit(FormEvent $event)
    {
        $data = $event->getData();
        $publication = null;
        $language = null;
        if (is_array($data) && array_key_exists('publication', $data) && $data['publication']) {
            $publication = (int) $data['publication'];
        }
        if (is_array($data) && array_key_exists('language', $data) && $data['language']) {
            $language = (int) $data['language'];
        }
        $this->addField($event->getForm(), $publication, $language);
    }
}
