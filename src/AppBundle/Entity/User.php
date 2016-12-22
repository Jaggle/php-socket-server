<?php
/**
 * Created by PhpStorm.
 * User: Jake
 * Date: 2016/12/23
 * Time: 0:24
 */

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use SensioLabs\Security\Exception\RuntimeException;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class User
 */
class User implements UserInterface
{
	private $username;

	private $salt;

	/**
	 * @var array
	 */
	private $roles;


	public function getUsername()
	{
		return $this->username;
	}

	public function getPassword()
	{
		throw new RuntimeException('can not get user password');
	}

	public function getSalt()
	{
		return $this->salt;
	}

	public function getRoles()
	{
		return $this->roles;
	}

	public function eraseCredentials()
	{
		// TODO: Implement eraseCredentials() method.
	}

}