<?php
/**
 * Created by PhpStorm.
 * User: Jake
 * Date: 2016/12/24
 * Time: 13:38
 */

namespace AppBundle\Repository;


use AppBundle\Entity\Message;
use Doctrine\ORM\EntityRepository;

/**
 * Class MessageRepository
 * @package AppBundle\Repository
 */
class MessageRepository extends EntityRepository
{
	public function create($from, $to, $content)
	{
		$message = new Message();
		$message->setSender($from);
		$message->setReceiver($to);
		$message->setContent($content);
		$message->setCreatetime(new \DateTime());
		
		$this->getEntityManager()->persist($message);
		$this->getEntityManager()->flush();
	}
}