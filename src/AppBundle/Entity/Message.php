<?php
/**
 * Created by PhpStorm.
 * User: Jake
 * Date: 2016/12/23
 * Time: 0:23
 */

namespace AppBundle\Entity;
use Doctrine\ORM\Mapping as ORM;

/**
 * Message
 * 
 * @ORM\Table(name="message")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\MessageRepository")
 */
class Message
{
	/**
	 * @var int
	 *
	 * @ORM\Column(name="id", type="integer")
	 * @ORM\Id()
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	private $id;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="content", type="string")
	 */
	private $content;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="sender", type="string")
	 */
	private $sender;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="receiver", type="string")
	 */
	private $receiver;

	/**
	 * @var \DateTime
	 *
	 * @ORM\Column(name="createtime", type="datetime")
	 */
	private $createtime;

	/**
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @param int $id
	 */
	public function setId($id)
	{
		$this->id = $id;
	}

	/**
	 * @return string
	 */
	public function getContent()
	{
		return $this->content;
	}

	/**
	 * @param string $content
	 */
	public function setContent($content)
	{
		$this->content = $content;
	}

	/**
	 * @return string
	 */
	public function getSender()
	{
		return $this->sender;
	}

	/**
	 * @param string $sender
	 */
	public function setSender($sender)
	{
		$this->sender = $sender;
	}

	/**
	 * @return string
	 */
	public function getReceiver()
	{
		return $this->receiver;
	}

	/**
	 * @param string $receiver
	 */
	public function setReceiver($receiver)
	{
		$this->receiver = $receiver;
	}

	/**
	 * @return \DateTime
	 */
	public function getCreatetime()
	{
		return $this->createtime;
	}

	/**
	 * @param \DateTime $createtime
	 */
	public function setCreatetime($createtime)
	{
		$this->createtime = $createtime;
	}


	
}