<?php
/**
 * @package   Newscoop\IngestPluginBundle
 * @author    Mischa Gorinskat <mischa.gorinskat@sourcefabric.org>
 * @copyright 2013 Sourcefabric o.p.s.
 * @license   http://www.gnu.org/licenses/gpl-3.0.txt
 */

namespace Newscoop\IngestPluginBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Output\OutputInterface;

/**
 * Ingest list feeds command
 */
class ListCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
        ->setName('ingest:list')
        ->setDescription('List all available feeds.')
        ->setHelp(<<<EOT
List all feeds available for ingesting.
EOT
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $feeds = $em
            ->getRepository('\Newscoop\IngestPluginBundle\Entity\Feed')
            ->findAll();

        $output->writeln('  '.str_pad('ID', 4, ' ', STR_PAD_LEFT) .'  Name');

        foreach ($feeds as $feed) {
            $output->writeln('  '.str_pad($feed->getId(), 4, ' ', STR_PAD_LEFT) .'  '.$feed->getName());
        }
    }
}
