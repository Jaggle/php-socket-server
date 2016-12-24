<?php
/**
 * Created by PhpStorm.
 * User: Jake
 * Date: 2016/12/23
 * Time: 0:05
 */

namespace AppBundle\Command\Socket;


use Support\Socket\Config;
use Support\Socket\Socket;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RunSocketCommand extends ContainerAwareCommand
{
	public function configure()
	{
		$this->setName('socket:run');
	}

	public function execute(InputInterface $input, OutputInterface $output)
	{
		
		if (!function_exists('socket_create')) {
			throw new \RuntimeException('We need function socket_create to go on...');
		}
		$host = $this->getContainer()->getParameter('socket.host');
		$port = $this->getContainer()->getParameter('socket.port');
		$socket = new Socket($host, $port, $this->getContainer());
		$socket->run();
	}
}