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

class AddUrlFieldSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(FormEvents::PRE_SET_DATA => 'preSetData', FormEvents::PRE_SUBMIT => 'preSubmit');
    }

    public function addField(Form $form, $parser = null)
    {
        if ($parser === null) {
            return false;
        }

        $options = $form->getConfig()->getOptions();
        $em = $options['em'];

        if (!($parser instanceof Newscoop\IngestPluginBundle\Entity\Parser)) {
            $parser = $em
                ->getRepository('Newscoop\IngestPluginBundle\Entity\Parser')
                ->findOneById($parser);
        }

        if ($parser->requiresUrl()) {

            $form
                ->add('url', 'url', array(
                    'label' => 'plugin.ingest.feeds.url',
                    'required' => true
                ));
        }
    }

    public function preSetData(FormEvent $event)
    {
        $data = $event->getData();
        $parser = $data ? $data->getParser() : null;
        $this->addField($event->getForm(), $parser);
    }

    public function preSubmit(FormEvent $event)
    {
        $data = $event->getData();
        $parser = null;
        if (is_array($data) && array_key_exists('parser', $data) && $data['parser']) {
            $parser = (int) $data['parser'];
        }
        $this->addField($event->getForm(), $parser);
    }
}
