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

class AddSectionFieldSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(FormEvents::PRE_SET_DATA => 'preSetData', FormEvents::PRE_SUBMIT => 'preSubmit');
    }

    public function addField(Form $form, $issue = null, $publication = null)
    {
        $options = $form->getConfig()->getOptions();

        if ($issue == null) {
            if ($publication !== null) {
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
            } else {
                return false;
            }
        }

        $form->add('sections', 'entity', array(
            'class' => 'Newscoop\Entity\Section',
            'property' => 'name',
            'multiple' => true,
            'expanded' => true,
            'label' => 'plugin.ingest.feeds.section',
            'query_builder' => function (EntityRepository $er) use ($issue) {
                return $er->createQueryBuilder('s')
                    ->where('s.issue = :issue')
                    ->setParameter('issue', $issue);
            },
            'attr' => array(
                'help_text' => 'plugin.ingest.feeds.form.help.section'
            )
        ));
    }

    public function preSetData(FormEvent $event)
    {
        $data = $event->getData();
        $issue = $data ? $data->getIssue() : null;
        $publication = $data ? $data->getPublication() : null;
        $this->addField($event->getForm(), $issue, $publication);
    }

    public function preSubmit(FormEvent $event)
    {
        $data = $event->getData();
        $issue = null;
        $publication = null;
        if (is_array($data) && array_key_exists('issue', $data) && $data['issue']) {
            $issue = (int) $data['issue'];
        }
        if (is_array($data) && array_key_exists('publication', $data) && $data['publication']) {
            $publication = (int) $data['publication'];
        }
        $this->addField($event->getForm(), $issue, $publication);
    }
}
