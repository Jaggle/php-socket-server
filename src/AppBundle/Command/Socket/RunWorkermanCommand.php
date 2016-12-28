<?php
/**
 * Created by PhpStorm.
 * User: Jake
 * Date: 2016/12/28
 * Time: 10:38
 */

namespace AppBundle\Command\Socket;


use PHPSocketIO\Socket;
use PHPSocketIO\SocketIO;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Workerman\Connection\ConnectionInterface;
use Workerman\Lib\Timer;
use Workerman\Worker;

class RunWorkermanCommand extends ContainerAwareCommand
{
	protected function configure()
	{
		$this->setName('workerman:run');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$socketIO = new SocketIO(2120);
		
		$socketIO->on('connection', function (Socket $socket) {

			$socket->on('login', function ($uid) use ($socket) {
				
				global $uidConnectionMap, $last_online_count, $last_online_page_count;
				
				if (isset($socket->uid)) {
					return;
				}
				
				$uid = (string)$uid;
				if (!isset($uidConnectionMap[$uid])) {
					$uidConnectionMap[$uid] = 0;
				}
				
				++$uidConnectionMap[$uid];
				
				$socket->join($uid);
				$socket->uid = $uid;

				$socket->emit('new_msg', $uid . '登录了!');
				$socket->emit('update_online_count', "当前<b>{$last_online_count}</b>人在线，共打开<b>{$last_online_page_count}</b>个页面");
				
			});

			$socket->on('disconnect', function () use ($socket) {
				global $uidConnectionMap;

				if (!isset($socket->uid)) {
					return;
				}

				if (--$uidConnectionMap[$socket->uid] <= 0) {
					unset($uidConnectionMap[$socket->uid]);
				}

			});

		});

		$socketIO->on('workerStart', function() {
			$httpServer = new Worker('http://0.0.0.0:2121');

			$httpServer->onMessage = function (ConnectionInterface $connection) {
				global $uidConnectionMap;
				$_POST = $_POST ? $_POST : $_GET;

				switch (@$_POST) {
					case 'publish':
						/**
						 * @var SocketIO $socketIO
						 */
						global $socketIO;

						$to               = @$_POST['to'];
						$_POST['content'] = htmlspecialchars(@$_POST['content']);

						if ($to) {
							$socketIO->to($to)->emit('new_msg', $_POST['content']);
							// 否则向所有uid推送数据
						} else {
							$socketIO->emit('new_msg', @$_POST['content']);
						}

						if ($to && !isset($uidConnectionMap[$to])) {
							return $connection->send('offline');
						} else {
							return $connection->send('ok');
						}
				}
				
				return $connection->send('fail');
			};
			
			$httpServer->listen();
			
			Timer::add(1, function () {
				/**
				 * @var SocketIO $socketIO
				 */
				global $uidConnectionMap, $socketIO, $last_online_count, $last_online_page_count;
				
				$online_count_now      = count($uidConnectionMap);
				$online_page_count_now = array_sum($uidConnectionMap);
				
				if ($last_online_count != $online_count_now || $last_online_page_count != $online_page_count_now) {
					$socketIO->emit('update_online_count', "当前<b>{$online_count_now}</b>人在线，共打开<b>{$online_page_count_now}</b>个页面");
					$last_online_count      = $online_count_now;
					$last_online_page_count = $online_page_count_now;
				}
			});
		});
		
		if (!defined('GLOBAL_START')) {
			Worker::runAll();
		}

	}
}