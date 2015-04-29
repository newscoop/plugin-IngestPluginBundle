<?php

namespace Newscoop\IngestPluginBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @Route("/admin/ingest/settings")
 */
class SettingsController extends Controller
{
    const INGEST_CRON_NAME = 'Ingest plugin cron job';

    /**
     * @Route("/index/")
     * @Template()
     */
    public function indexAction(Request $request)
    {
        $user = $this->get('user')->getCurrentUser();
        if (!$user->hasPermission('plugin_ingest_settings')) {
            return $this->redirect($this->generateUrl('newscoop_ingestplugin_entry_list'));
        }

        $em = $this->get('em');
        $translator = $this->get('translator');

        $ingestCron = $em->getRepository('Newscoop\Entity\CronJob')->findOneByName(self::INGEST_CRON_NAME);

        $defaultData = array('cron_custom' => $ingestCron->getSchedule());
        $form = $this->createFormBuilder($defaultData)
            ->add('cron_custom', 'text', array(
                'label' => 'plugin.ingest.settings.form.label.cron_custom',
                'required' => true,
                'attr' => array(
                    'help_text' => 'plugin.ingest.settings.form.help_text.cron_custom'
                )
            ))
            ->add('save', 'submit', array(
                'label' => 'plugin.ingest.settings.form.label.submit'
            ))
            ->getForm();

        if ($request->getMethod() == 'POST') {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $data = $form->getData();

                // Add cornjob stuff
                if (array_key_exists('cron_custom', $data) && $data['cron_custom']) {
                    $cronString = $data['cron_custom'];

                    try {
                        $cronExpression = \Cron\CronExpression::factory($cronString);
                        $ingestCron->setSchedule($cronString);
                        $em->persist($ingestCron);
                    } catch (\Exception $e) {
                        $form->get('cron_custom')->addError(new FormError($e->getMessage()));
                    }

                    $em->flush();
                }

                $this->get('session')->getFlashBag()->add(
                    'notice',
                    $translator->trans('plugin.ingest.settings.status.success')
                );
            }
        }

        return array(
            'form' => $form->createView()
        );
    }
}
