<?php

namespace Newscoop\IngestPluginBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Finder\Finder;

use Newscoop\IngestPluginBundle\Form\Type\FeedType;
use Newscoop\IngestPluginBundle\Entity\Feed;
use Newscoop\IngestPluginBundle\Entity\Feed\Entry;
use Newscoop\IngestPluginBundle\Entity\Parser;
use Newscoop\EventDispatcher\Events\GenericEvent;

/**
 * @Route("/admin/ingest")
 */
class AdminController extends Controller
{
    /**
     * @Route("/entry")
     * @Template()
     */
    public function entryAction(Request $request)
    {
        $em = $this->container->get('em');

        // Todo: remove after debugging is done
        // Debug to install entities
        $tool = new \Doctrine\ORM\Tools\SchemaTool($em);
        $tool->updateSchema(array(
            $em->getClassMetadata('Newscoop\IngestPluginBundle\Entity\Feed'),
            $em->getClassMetadata('Newscoop\IngestPluginBundle\Entity\Feed\Entry'),
            $em->getClassMetadata('Newscoop\IngestPluginBundle\Entity\Parser'),
        ), true);

        // $dispatcher = $this->get('event_dispatcher');
        // $dispatcher->dispatch('plugin.install.newscoop_ingest_plugin', new GenericEvent());
        // $dispatcher->dispatch('plugin.remove.newscoop_ingest_plugin', new GenericEvent());

        // End of debug code

        $entries = $em->getRepository('Newscoop\IngestPluginBundle\Entity\Feed\Entry')
            ->createQueryBuilder('e')
            ->getQuery()
            ->getResult();

        return array(
            'entries' => $entries
        );
    }

    /**
     * @Route("/entry/publish/{id}")
     * @ParamConverter("get")
     */
    public function entryPublishAction(Request $request, Entry $entry)
    {
        $publisherService = $this->container->get('newscoop_ingest_plugin.publisher');
        $publisherService->publish($entry);

        die('EOA');
    }

    /**
     * @Route("/entry/prepare/{id}")
     * @Template()
     */
    public function entryPrepareAction($id, Request $request)
    {

    }

    /**
     * @Route("/entry/delete/{id}")
     * @Template()
     */
    public function entryDeleteAction($id, Request $request)
    {

    }

    /**
     * @Route("/feed")
     * @Template()
     */
    public function feedAction(Request $request)
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
     * @Route("/feed/add")
     * @Template()
     */
    public function feedAddAction(Request $request)
    {
        $feed = new Feed();

        $form = $this->createForm(new FeedType(), $feed);

        // Handle updates in form
        if ($request->isXmlHttpRequest()) {
            $form->handleRequest($request);

            return new JsonResponse(array(
                'html' => htmlentities($this-> renderView('NewscoopIngestPluginBundle:Admin:FeedType.html.twig', array(
                    'form'   => $form->createView(),
                ))),
            ));
        }

        if ($request->getMethod() == 'POST') {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $em = $this->getDoctrine()->getManager();
                $em->persist($feed);
                $em->flush();

                $this->get('session')->getFlashBag()->add(
                    'notice',
                    'Feed added!'
                );

                return $this->redirect($this->generateUrl('newscoop_ingestplugin_admin_feed'));
            }
        }

        return array(
            'form' => $form
        );
    }

    /**
     * @Route("/feed/edit/{id}")
     * @Template()
     */
    public function feedEditAction($id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $feed = $em->getRepository('Newscoop\IngestPluginBundle\Entity\Feed')->find($id);
        if (!$feed) {
            throw $this->createNotFoundException(
                'No feed found for id ' . $id
            );
        }

        $form = $this->createForm(new FeedType(), $feed);

        // Handle updates in form
        if ($request->isXmlHttpRequest()) {
            $form->handleRequest($request);

            return new JsonResponse(array(
                'html' => htmlentities($this->render('NewscoopIngestPluginBundle:Admin:FeedForm.html.twig', array(
                    'form'   => $form->createView(),
                ))),
            ));
        }

        if ($request->getMethod() == 'POST') {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $em = $this->getDoctrine()->getManager();
                $em->persist($feed);
                $em->flush();

                $this->get('session')->getFlashBag()->add(
                    'notice',
                    'Feed updated!'
                );

                return $this->redirect($this->generateUrl('newscoop_ingestplugin_admin_feed'));
            }
        }

        return array(
            'form' => $form
        );
    }

    /**
     * @Route("/feed/delete/{id}")
     */
    public function feedDeleteAction($id, Request $request)
    {
        $em     = $this->getDoctrine()->getManager();
        $feed   = $em->getRepository('Newscoop\IngestPluginBundle\Entity\Feed')->find($id);
        if (!$feed) {
            throw $this->createNotFoundException(
                'No feed found for id ' . $id
            );
        }

        $em->remove($feed);
        $em->flush();

        $this->get('session')->getFlashBag()->add(
            'notice',
            'Feed deleted!'
        );

        return $this->redirect($this->generateUrl('newscoop_ingestplugin_admin_feed'));
    }

    /**
     * @Route("/parser")
     * @Template()
     */
    public function parserAction(Request $request)
    {
        $em = $this->container->get('em');

        $parsers = $em->getRepository('Newscoop\IngestPluginBundle\Entity\Parser')
            ->createQueryBuilder('p')
            ->getQuery()
            ->getResult();

        return array(
            'parsers' => $parsers
        );
    }

    /**
     * @Route("/parser/add")
     * @Template()
     */
    public function parserAddAction(Request $request)
    {
        $em                 = $this->container->get('em');
        $finder             = new Finder();
        $namespacePrefix    = '\\Newscoop\\IngestPluginBundle\\Parsers\\';
        $fileNamespaces     = array();
        $form               = null;

        // Get parsers from directory
        $finder->files()->in(__DIR__ . '/../Parsers/')->name('*.php')->notName('Parser.php');
        foreach ($finder as $file) {
            $fileNamespaces[] = $namespacePrefix . str_replace('.php', '', basename($file->getRelativePathname()));
        }

        // Get installed parsers
        $dbResult = $em->getRepository('Newscoop\IngestPluginBundle\Entity\Parser')
            ->getParserNamespaces();

        $dbNamespaces = ($dbResult !== null) ? array_map('current', $dbResult) : array();

        // Filter found parsers with already installed parsers
        $newParsers = array_diff($fileNamespaces, $dbNamespaces);

        if (count($newParsers) > 0) {

            foreach ($newParsers AS $key => $value) {
                $newParsers[$value] = $value;
                unset($newParsers[$key]);
            }

            $formBuilder = $this->createFormBuilder()
                ->add('namespace', 'choice', array(
                    'choices' => $newParsers,
                    'multiple' => true,
                    'expanded' => true,
                    'label' => 'Select one or more parsers to install'
                ))
                ->add('save', 'submit', array(
                    'label' => 'plugin.ingest.parsers.install'
                ));
            $form = $formBuilder->getForm();

            $form->handleRequest($request);

            if ($form->isValid()) {

                $fileSystem = new Filesystem();

                $data = $form->getData();

                if (count($data['namespace']) > 0) {
                    foreach ($data['namespace'] AS $namespace) {
                        $className  = substr($namespace, strrpos($namespace, '\\')+1);
                        $filename   = $className .'.php';

                        try {
                            if ($fileSystem->exists(__DIR__ . '/../Parsers/' . $filename)) {

                                $included = include(__DIR__ . '/../Parsers/' . $filename);

                                $parser = new Parser();
                                $parser
                                    ->setName($namespace::$parserName)
                                    ->setDescription($namespace::$parserDescription)
                                    ->setDomain($namespace::$parserDomain)
                                    ->setNamespace($namespace);

                                $em->persist($parser);
                            }
                        } catch (IOException $e) {
                            echo 'Parser file does not exist.';
                        }
                    }

                    $em->flush();
                }

                $this->get('session')->getFlashBag()->add(
                    'notice',
                    'Parser(s) installed!'
                );

                return $this->redirect($this->generateUrl('newscoop_ingestplugin_admin_parser'));
            }
        }

        return array(
            'form' => $form
        );
    }

    /**
     * @Route("/parser/delete/{id}")
     */
    public function parserDeleteAction($id, Request $request) {

        $em     = $this->getDoctrine()->getManager();
        $parser = $em->getRepository('Newscoop\IngestPluginBundle\Entity\Parser')->find($id);
        if (!$parser) {
            throw $this->createNotFoundException(
                'No parser found for id ' . $id
            );
        }

        $em->remove($parser);
        $em->flush();

        $this->get('session')->getFlashBag()->add(
            'notice',
            'Parser deleted!'
        );

        return $this->redirect($this->generateUrl('newscoop_ingestplugin_admin_parser'));
    }
}
