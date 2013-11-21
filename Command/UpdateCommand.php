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
 * Ingest update command
 */
class UpdateCommand extends ContainerAwareCommand
{
    /**
     * @see Console\Command\Command
     */
    protected function configure()
    {
        $this
        ->setName('ingest:update')
        ->setDescription('Ingest a specific or all feeds.')
        ->setHelp(<<<EOT
Use value <info>all</info> as parameter to update all feeds or specify a specific <info>feed ID.</info>
EOT
        )
        ->addArgument('feed', InputArgument::REQUIRED, 'Which feed do you want to update?');

    }

    /**
     * @see Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $feedParam  = $input->getArgument('feed');
        $ingest     = $this->getContainer()->getService('newscoop_ingest_plugin.ingester');

        if ($feedParam === 'all') {
            //$ingest->ingestAllFeeds();
        } else {
            $em = $this->getContainer()->get('doctrine')->getManager();
            $feedEntity = $em
                ->getRepository('\Newscoop\IngestPluginBundle\Entity\Feed')
                ->find($feedParam);
            if ($feedEntity === null) {
                $output->writeln('No feed found with specified id.');
            } else {
                $ingest->updateFeed($feedEntity);
            }
        }
    }
}
