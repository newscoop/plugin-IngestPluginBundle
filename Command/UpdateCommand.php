<?php
/**
 * @package   Newscoop\IngestPluginBundle
 * @author    Mischa Gorinskat <mischa.gorinskat@sourcefabric.org>
 * @copyright 2013 Sourcefabric o.p.s.
 * @license   http://www.gnu.org/licenses/gpl-3.0.txt
 */

namespace Newscoop\IngestPluginBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

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
        global $g_ado_db;
        $container = $this->getApplication()->getKernel()->getContainer();
        $g_ado_db = $container->get('doctrine.adodb');

        $feedParam  = $input->getArgument('feed');
        $ingest     = $this->getContainer()->getService('newscoop_ingest_plugin.ingester');

        if ($feedParam === 'all') {
            $feedsUpdated = $ingest->ingestAllFeeds();
            $output->writeln('<info>Total feeds updated: '.$feedsUpdated.'</info>');
        } else {
            $em = $this->getContainer()->get('doctrine')->getManager();
            $feedEntity = $em
                ->getRepository('\Newscoop\IngestPluginBundle\Entity\Feed')
                ->findOneById($feedParam);
            if ($feedEntity === null) {
                $output->writeln('<error>No feed found with specified id.</error>');
            } else {
                try {
                    $ingest->updateFeed($feedEntity);
                } catch(\Exception $e) {
                    $output->writeln('<error>'.$e->getMessage().'</error>');
                }
            }
        }
    }
}
