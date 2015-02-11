<?php

namespace Newscoop\IngestPluginBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Newscoop\IngestPluginBundle\Event\IngestParsersEvent;
use Newscoop\IngestPluginBundle\Entity\Parser;


/**
 * @Route("/admin/ingest/parser")
 */
class ParserController extends Controller
{
    /**
     * @Route("/list")
     * @Template()
     */
    public function listAction(Request $request)
    {
        $em = $this->container->get('em');

        $parsers = $em->getRepository('Newscoop\IngestPluginBundle\Entity\Parser')
            ->getParsers(false);

        return array(
            'parsers' => $parsers
        );
    }

    /**
     * @Route("/find_new")
     */
    public function updateAction()
    {
        $this->get('dispatcher')->dispatch('newscoop_ingest.parser.register', new IngestParsersEvent($this, array()));

        $this->get('session')->getFlashBag()->add(
            'notice',
            $this->container->get('translator')->trans('plugin.ingest.parsers.updatedsuccess')
        );

        return $this->redirect($this->generateUrl('newscoop_ingestplugin_parser_list'));
    }

    /**
     * @Route("/change_status/{id}")
     * @ParamConverter("get")
     */
    public function changeStatusAction(Request $request, Parser $parser)
    {
        $status = true;
        $message = $message = $this->get('translator')->trans(
            'plugin.ingest.parsers.activationsuccess',
            array('%parser%' => $parser->getName())
        );

        $em = $this->get('em');

        try {
            $active = $parser->getActive();
            $parser->setActive(!$active);

            $em->persist($parser);
            $em->flush();
        } catch (\Exception $e) {
            $status = false;
            $message = $this->get('translator')->trans(
                'plugin.ingest.parsers.activationfailed',
                array('%parser%' => $parser->getName(), '%error%' => $e->getMessage())
            );
        }

        return new JsonResponse(array(
            'status' => $status,
            'message' => $message
        ));
    }
}
