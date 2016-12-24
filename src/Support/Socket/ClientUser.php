<?php
/**
 * Created by PhpStorm.
 * User: Jake
 * Date: 2016/12/24
 * Time: 11:34
 */

namespace Support\Socket;
use AppBundle\Entity\User;

/**
 * 每个在聊天环境中的用户都是一个User实例
 * 
 * Class ClientUser
 * @package Support\Socket
 */
class ClientUser
{
	/**
	 * @var resource
	 */
	private $socket;
	
	/**
	 * @var User
	 */
	private $user;
	
	/**
	 * @var string
	 */
	private $username;
	
	/**
	 * @var string
	 */
	private $nickname;
	
	/**
	 * @var string
	 */
	private $clientId;
	
	/**
	 * @var bool
	 */
	private $handshaked;

	public function __construct($socket, $handshaked)
	{
		$this->socket = $socket;
		$this->handshaked = $handshaked;
	}
	
	/**
	 * @return resource
	 */
	public function getSocket()
	{
		return $this->socket;
	}
	
	/**
	 * @param resource $socket
	 */
	public function setSocket($socket)
	{
		$this->socket = $socket;
	}
	
	/**
	 * @return User
	 */
	public function getUser()
	{
		return $this->user;
	}
	
	/**
	 * @param User $user
	 */
	public function setUser($user)
	{
		$this->user = $user;
	}
	
	/**
	 * @return string
	 */
	public function getUsername()
	{
		return $this->username;
	}
	
	/**
	 * @param string $username
	 */
	public function setUsername($username)
	{
		$this->username = $username;
	}
	
	/**
	 * @return string
	 */
	public function getClientId()
	{
		return $this->clientId;
	}
	
	/**
	 * @param string $clientId
	 */
	public function setClientId($clientId)
	{
		$this->clientId = $clientId;
	}
	
	/**
	 * @return boolean
	 */
	public function isHandshaked()
	{
		return $this->handshaked;
	}
	
	/**
	 * @param boolean $handshaked
	 */
	public function setHandshaked($handshaked)
	{
		$this->handshaked = $handshaked;
	}
	
	/**
	 * @return string
	 */
	public function getNickname()
	{
		return $this->nickname;
	}
	
	/**
	 * @param string $nickname
	 */
	public function setNickname($nickname)
	{
		$this->nickname = $nickname;
	}
	
	
	
}