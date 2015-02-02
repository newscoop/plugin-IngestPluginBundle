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

use Newscoop\IngestPluginBundle\Entity\Parser;
use Newscoop\EventDispatcher\Events\GenericEvent;
use Newscoop\NewscoopException;

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
            ->createQueryBuilder('p')
            ->getQuery()
            ->getResult();

        return array(
            'parsers' => $parsers
        );
    }

    /**
     * @Route("/add")
     * @Template()
     */
    public function addAction(Request $request)
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
                    'label' => 'plugin.ingest.parsers.form.label.namespace'
                ))
                ->add('install', 'submit', array(
                    'label' => 'plugin.ingest.parsers.form.button.install'
                ))
                ->add('cancel', 'button', array(
                    'label' => 'plugin.ingest.parsers.form.button.cancel'
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
                            throw new NewscoopException($this->container->get('translator')->trans('plugin.ingest.parsers.parserfilenotexists'));
                        }
                    }

                    $em->flush();
                }

                $this->get('session')->getFlashBag()->add(
                    'notice',
                    $this->container->get('translator')->trans('plugin.ingest.parsers.installedsuccess')
                );

                return $this->redirect($this->generateUrl('newscoop_ingestplugin_parser_list'));
            }
        }

        return array(
            'form' => $form
        );
    }

    /**
     * @Route("/delete/{id}")
     * @ParamConverter("get")
     */
    public function deleteAction(Request $request, Parser $parser) {

        $em     = $this->getDoctrine()->getManager();
        $em->remove($parser);
        $em->flush();

        $this->get('session')->getFlashBag()->add(
            'notice',
            $this->container->get('translator')->trans('plugin.ingest.parsers.deletedsuccess')
        );

        return $this->redirect($this->generateUrl('newscoop_ingestplugin_parser_list'));
    }
}
