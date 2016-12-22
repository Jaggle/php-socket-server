<?php
/**
 * Created by PhpStorm.
 * User: Jake
 * Date: 2016/12/23
 * Time: 0:09
 */

namespace Support\Socket;


class Config
{
	private $host = '127.0.0.1';
	private $port = 4959;
	
	/**
	 * @return string
	 */
	public function getHost()
	{
		return $this->host;
	}
	
	/**
	 * @param string $host
	 */
	public function setHost($host)
	{
		$this->host = $host;
	}
	
	/**
	 * @return int
	 */
	public function getPort()
	{
		return $this->port;
	}
	
	/**
	 * @param int $port
	 */
	public function setPort($port)
	{
		$this->port = $port;
	}
	
	
}