<?php
/**
 * @package Newscoop
 * @copyright 2011 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

namespace Newscoop\IngestPluginBundle\Entity\Repository;

use DateTime;
use Doctrine\ORM\EntityRepository;

/**
 * Feed repository
 */
class FeedRepository extends EntityRepository
{
    public function getFeeds() {

        $query = $this->getEntityManager()
            ->createQuert('SELECT f.id, f.title FROM '.$this->getEntityName().' AS f');
        $feeds = $query->getResult();

        if (empty($feeds)) {
            return null;
        } else {
            return $feeds;
        }
    }
}
