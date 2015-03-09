<?php
/**
 * @package Newscoop\IngestPluginBundle
 * @author Mischa Gorinskat <mischa.gorinskat@sourcefabric.org>
 * @copyright 2015 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

namespace Newscoop\IngestPluginBundle\EventListener;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Filesystem;
use Newscoop\IngestPluginBundle\Entity\Parser;
use Newscoop\IngestPluginBundle\Event\IngestParsersEvent;

/**
 * Ingest parsers listener
 */
class IngestParsersListener
{
    private $em;

    public function __construct($em)
    {
        $this->em = $em;
    }

    /**
     * Register external parsers
     *
     * @param IngestParsersEvent $event
     */
    public function registerExternalParsers(IngestParsersEvent $event)
    {
        $this->installParsers($event);
    }

    /**
     * Install external parsers
     *
     * @return void
     *
     * @throws Exception
     */
    private function installParsers(IngestParsersEvent $event)
    {
        $fs = new Filesystem();
        $finder = new Finder();

        try {
            $pluginsDir = __DIR__ . '/../../../';

            $iterator = $finder
                ->ignoreUnreadableDirs()
                ->files()
                ->name('*Parser.php')
                ->notName('AbstractParser.php')
                // ->in($pluginsDir . '*/*/Parsers/IngestAdapters/')
                ->in(__DIR__  . '/../Parsers/');

            foreach ($iterator as $file) {
                $classNamespace = str_replace(realpath($pluginsDir), '', substr($file->getRealPath(), 0, -4));
                $namespace = str_replace('/', '\\', $classNamespace);

                $parserName = substr($file->getFilename(), 0, -4);

                $parser = $this->em->getRepository('Newscoop\IngestPluginBundle\Entity\Parser')
                    ->findOneByNamespace($namespace);

                $event->registerParser($parserName, array(
                    'class' => $namespace,
                ));

                if (!$parser) {
                    $parser = new Parser();
                }
                $parser
                    ->setName($namespace::getParserName())
                    ->setDescription($namespace::getParserDescription())
                    ->setDomain($namespace::getParserDomain())
                    ->setRequiresUrl($namespace::getRequiresUrl())
                    ->setSectionHandling($namespace::getHandlesSection())
                    ->setNamespace($namespace);

                $this->em->persist($parser);
            }

            $this->em->flush();
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }
}
