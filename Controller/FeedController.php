<?php

namespace Newscoop\IngestPluginBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Newscoop\IngestPluginBundle\Form\Type\FeedType;
use Newscoop\IngestPluginBundle\Entity\Feed;
use Newscoop\IngestPluginBundle\Entity\Feed\Entry;
use Newscoop\IngestPluginBundle\Entity\Parser;
use Newscoop\EventDispatcher\Events\GenericEvent;

/**
 * @Route("/admin/ingest/feed")
 */
class FeedController extends Controller
{
    /**
     * @Route("/list/")
     * @Template()
     */
    public function listAction(Request $request)
    {
        $em = $this->container->get('em');

        $feeds = $em->getRepository('Newscoop\IngestPluginBundle\Entity\Feed')
            ->createQueryBuilder('f')
            ->getQuery()
            ->getResult();

        return array(
            'feeds' => $feeds
        );
    }

    /**
     * @Route("/add/", name="newscoop_ingestplugin_feed_add")
     * @Route("/edit/{id}/", name="newscoop_ingestplugin_feed_edit")
     * @ParamConverter("get")
     * @Template()
     */
    public function formAction(Request $request, Feed $feed = null)
    {
        $type = 'edit';
        $message = 'updatedsuccess';
        if (!($feed instanceof \Newscoop\IngestPluginBundle\Entity\Feed)) {
            $feed = new Feed();
            $type = 'add';
            $message = 'addedsuccess';
        }

        $em = $this->get('em');

        $form = $this->createForm(new FeedType(), $feed, array(
            'em' => $em,
            'type' => $type
        ));

        // Handle updates in form
        if ($request->isXmlHttpRequest()) {
            $form->handleRequest($request);

            return new JsonResponse(array(
                'html' => htmlentities($this-> renderView('NewscoopIngestPluginBundle:Form:ajaxform.html.twig', array(
                    'form'   => $form->createView(),
                ))),
            ));
        }

        if ($request->getMethod() == 'POST') {
            $form->handleRequest($request);

            if ($form->isValid()) {

                $em->persist($feed);
                $em->flush();

                $this->get('session')->getFlashBag()->add(
                    'notice',
                    $this->get('translator')->trans(sprintf('plugin.ingest.feeds.%s', $message))
                );

                return $this->redirect($this->generateUrl('newscoop_ingestplugin_feed_list'));
            }
        }

        return array(
            'form' => $form
        );
    }

    /**
     * @TODO: @Route("/edit/{id}/")
     * @TODO: @ParamConverter("get")
     * @TODO: @Template()
     */
    // public function editAction(Request $request, Feed $feed)
    // {
    //     $em = $this->container->get('em');

    //     $form = $this->createForm(new FeedType(), $feed, array('type' => 'edit'));

    //     // Handles updates in form
    //     if ($request->isXmlHttpRequest()) {
    //         $form->handleRequest($request);

    //         return new JsonResponse(array(
    //             'html' => htmlentities($this-> renderView('NewscoopIngestPluginBundle:Feed:ajaxForm.html.twig', array(
    //                 'form'   => $form->createView(),
    //             ))),
    //         ));
    //     }

    //     if ($request->getMethod() == 'POST') {
    //         $form->handleRequest($request);

    //         if ($form->isValid()) {
    //             $em->persist($feed);
    //             $em->flush();

    //             $this->get('session')->getFlashBag()->add(
    //                 'notice',
    //                 $this->container->get('translator')->trans('plugin.ingest.feeds.updatedsuccess')
    //             );

    //             return $this->redirect($this->generateUrl('newscoop_ingestplugin_feed_list'));
    //         }
    //     }

    //     return array(
    //         'form' => $form
    //     );
    // }

    /**
     * @Route("/delete/{id}/")
     * @ParamConverter("get")
     */
    public function deleteAction(Request $request, Feed $feed)
    {
        $em                     = $this->container->get('em');
        $publisherService       = $this->container->get('newscoop_ingest_plugin.publisher');
        $deleteRelatedEntries   = (bool) $request->query->get('delete_entries');

        if ($deleteRelatedEntries) {

            $entries = $em
                ->getRepository('Newscoop\IngestPluginBundle\Entity\Feed\Entry')
                ->findByFeed($feed);

            foreach ($entries as $entry) {
                if ($entry->getArticleId() !== null) {
                    $publisherService->remove($entry);
                }
                $em->remove($entry);
            }
        }

        $em     = $this->getDoctrine()->getManager();
        $em->remove($feed);
        $em->flush();

        $this->get('session')->getFlashBag()->add(
            'notice',
            $this->container->get('translator')->trans('plugin.ingest.feeds.deletedsuccess')
        );

        return $this->redirect($this->generateUrl('newscoop_ingestplugin_feed_list'));
    }

    /**
     * @Route("/edit/quick/{id}/{option}/{value}")
     * @ParamConverter("get")
     */
    public function quickEditAction(Request $request, Feed $feed, $option, $value)
    {
        $em = $this->container->get('em');
        $method = 'set'.$option;

        if (method_exists($feed, $method)) {

            try {
                $feed->$method($value);
                $em->persist($feed);
                $em->flush();
                $status = 'notice';
                $message = $this->container->get('translator')->trans(
                    'plugin.ingest.feeds.updatedpropertysucces',
                    array('%property%' => $option, '%value%' => $value, '%feed%' => $feed->getName())
                );
            } catch (\Exception $e) {
                $status = 'error';
                $message = $this->container->get('translator')->trans(
                    'plugin.ingest.feeds.updatedpropertyfailure'
                );
            }
        } else {
            $status = 'error';
            $message = $this->container->get('translator')->trans(
                'plugin.ingest.feeds.updatedinvalidmethod',
                array('%property%' => $option, '%value%' => $value, '%feed%' => $feed->getName())
            );
        }

        $this->get('session')->getFlashBag()->add(
            $status,
            $message
        );

        return $this->redirect($this->generateUrl('newscoop_ingestplugin_feed_list'));
    }

    /**
     * @Route("/update/all/")
     */
    public function updateAllAction(Request $request)
    {
        $ingestService = $this->container->get('newscoop_ingest_plugin.ingester');
        $updatedFeedCount = $ingestService->ingestAllFeeds();

        $this->get('session')->getFlashBag()->add(
            'notice',
            $this->container->get('translator')->get(
                'plugin.ingest.feeds.feedupdatedcount',
                array('%count%' => $updatedFeedCount)
            )
        );

        return $this->redirect($this->generateUrl('newscoop_ingestplugin_feed_list'));
    }

    /**
     * @Route("/update/{id}/")
     * @ParamConverter("get")
     */
    public function updateAction(Request $request, Feed $feed)
    {
        $ingestService = $this->container->get('newscoop_ingest_plugin.ingester');

        try {
            $ingestService->updateFeed($feed);

            $this->get('session')->getFlashBag()->add(
                'notice',
                $this->container->get('translator')->trans(
                    'plugin.ingest.feeds.feedupdated',
                    array('%feed%' => $feed->getName())
                )
            );
        } catch(\Exception $e) {
            $this->get('session')->getFlashBag()->add(
                'error',
                $this->container->get('translator')->trans(
                    'plugin.ingest.feeds.feedupdateerror',
                    array('%error%' => $e->getMessage())
                )
            );
        }

        return $this->redirect($this->generateUrl('newscoop_ingestplugin_feed_list'));
    }

    /**
     * @Route("/topics_search/")
     */
    public function findTopicsAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {

            // retrieve query and page_limit
            $q = $request->query->get('term');
            $p = $request->query->get('page_limit');

            $topics = $this
                ->get('em')
                ->getRepository('Newscoop\Entity\Topic')
                ->createQueryBuilder('t')
                ->select('t.id, t.name AS term')
                ->where('t.name LIKE :q')
                ->setParameter('q', '%'.$q.'%')
                ->setMaxResults($p)
                ->getQuery()
                ->getResult()
            ;

            $arr = array('results' => array('topics' => $topics));

            $response = new JsonResponse($arr);
            $response->setCallback($request->query->get('callback'));

            return $response;
        }

        throw new BadRequestHttpException('Only XHR supported');
    }
}
