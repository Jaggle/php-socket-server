<?php
/**
 * Created by PhpStorm.
 * User: Jake
 * Date: 2016/12/24
 * Time: 12:11
 */

namespace Support\Socket;


use Doctrine\Common\Collections\ArrayCollection;

class ClientUserCollection extends ArrayCollection
{
	/**
	 * @param int|string $key
	 * @return ClientUser
	 */
	public function get($key)
	{
		return parent::get($key);
	}
}