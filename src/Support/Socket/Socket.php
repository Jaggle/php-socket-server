<?php
/**
 * Created by PhpStorm.
 * User: Jake
 * Date: 2016/12/23
 * Time: 0:09
 */

namespace Support\Socket;


use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Socket
{
	public $sockets;
	
	/**
	 * @var ClientUserCollection
	 */
	public $users;
	public $master;
	private $container;
	
	/**
	 * @var EntityManager
	 */
	private $em;

	public function __construct($address, $port, ContainerInterface $container)
	{
		$this->master = $this->WebSocket($address, $port);
		$this->sockets = array('master' => $this->master);
		$this->users = new ClientUserCollection();
		$this->container = $container;
		$this->em = $container->get('doctrine')->getManager();
	}


	public function run()
	{
		$this->e('server is running...');
		while (true) {
			$reads = $this->sockets;
			$writes = null;
			$excepts = null;
			socket_select($reads, $writes, $excepts, null);
			foreach ($reads as $sock) {
				if ($sock == $this->master) {
					$client = socket_accept($this->master);
					$this->e('已经接受socket连接: ');
					$this->sockets[] = $client;
					$this->users->add(new ClientUser($client, false));
				} else {
					$len = socket_recv($sock, $buffer, 2048, 0);
					$k = $this->search($sock);
					if ($len < 7) {
						$name = $this->users->get($k)->getUsername();
						$this->close($sock);
						$this->sendDisconnectMessage($name, $k);
						continue;
					}
					$clientUser = $this->users->get($k);
					if (!$clientUser->isHandshaked()) {
						$this->handShake($k, $buffer);
					} else {
						$buffer = $this->unPack($buffer);
						$this->send($k, $buffer);
					}
				}
			}

		}

	}

	/**
	 * @param $sock     resource    socket对象
	 */
	public function close($sock)
	{
		$k = array_search($sock, $this->sockets);
		socket_close($sock);
		unset($this->sockets[$k]);
		$this->users->remove($k);
		$this->e("a connection is closed,key:$k");
	}

	/**
	 * @param $sock
	 * @return bool|int|string
	 */
	public function search($sock)
	{
		/**
		 * @var ClientUser $v
		 */
		foreach ($this->users->toArray() as $k => $v) {
			if ($sock == $v->getSocket())
				return $k;
		}
		return false;
	}

	/**
	 * 创建并且监听一个socket服务器,
	 *
	 * @param string $address
	 * @param int $port
	 * @return resource 返回当前当前socket服务对象
	 */
	public function WebSocket($address, $port)
	{
		$server = \socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		socket_set_option($server, SOL_SOCKET, SO_REUSEADDR, 1);
		socket_bind($server, $address, $port);
		socket_listen($server);
		$this->e('server start      : ' . date('Y-m-d H:i:s'));
		$this->e('listening on      : ' . $address . ' port ' . $port);
		return $server;
	}

	/**
	 *
	 * @param $k
	 * @param $buffer
	 * @return bool
	 */
	public function handShake($k, $buffer)
	{
		$buf = substr($buffer, strpos($buffer, 'Sec-WebSocket-Key:') + 18);    //返回buffer的子字符串
		$key = trim(substr($buf, 0, strpos($buf, "\r\n")));       //返回buffer的第一行

		$new_key = base64_encode(sha1($key . "258EAFA5-E914-47DA-95CA-C5AB0DC85B11", true));   //得到加密的字符串

		$new_message = "HTTP/1.1 101 Switching Protocols\r\n";
		$new_message .= "Upgrade: websocket\r\n";
		$new_message .= "Sec-WebSocket-Version: 13\r\n";
		$new_message .= "Connection: Upgrade\r\n";
		$new_message .= "Sec-WebSocket-Accept: " . $new_key . "\r\n\r\n";

		socket_write($this->users->get($k)->getSocket(), $new_message, strlen($new_message));
		$this->users->get($k)->setHandshaked(true);
		$this->e('握手成功！');
		return true;

	}

	/**
	 * 数据包解码
	 * @param $str
	 * @return bool|string
	 */
	public function unPack($str)
	{
		$mask = array();
		$data = '';
		$msg = unpack('H*', $str);  //unpack() 函数从二进制字符串$str对数据进行解包,返回一个数组
		$head = substr($msg[1], 0, 2);        //第二行元素的一个子串[三个字符]
		if (hexdec($head{1}) === 8) {       //hexdec() 函数把十六进制转换为十进制。
			$data = false;
		} else if (hexdec($head{1}) === 1) {
			$mask[] = hexdec(substr($msg[1], 4, 2));
			$mask[] = hexdec(substr($msg[1], 6, 2));
			$mask[] = hexdec(substr($msg[1], 8, 2));
			$mask[] = hexdec(substr($msg[1], 10, 2));

			$s = 12;
			$e = strlen($msg[1]) - 2;
			$n = 0;
			for ($i = $s; $i <= $e; $i += 2) {
				$data .= chr($mask[$n % 4] ^ hexdec(substr($msg[1], $i, 2)));
				$n++;
			}
		}
		return $data;
	}

	/**
	 * 数据包加码
	 * @param $msg
	 * @return string
	 */
	public function pack(array $msg)
	{
		$msg = json_encode($msg);
		$msg = preg_replace(array('/\r$/', '/\n$/', '/\r\n$/',), '', $msg);
		$frame = array();
		$frame[0] = '81';
		$len = strlen($msg);
		$frame[1] = $len < 16 ? '0' . dechex($len) : dechex($len);
		$frame[2] = $this->ord_hex($msg);
		$data = implode('', $frame);
		return pack("H*", $data);
	}

	/**
	 * $data数据
	 * @param $data
	 * @return string
	 */
	public function ord_hex($data)
	{
		$msg = '';
		$l = strlen($data);
		for ($i = 0; $i < $l; $i++) {
			$msg .= dechex(ord($data{$i}));
		}
		return $msg;
	}

	/**
	 * 发送消息的方法
	 * @param $k    int     规定发送的用户的id
	 * @param $receive  string  接收到的字符
	 */
	public function send($k, $receive)
	{
		if(empty($receive)) {
			return;
		}

		$this->e('[chat ' . date("Y-m-d H:i:s") . ']' . $receive);

		$receive = json_decode($receive, true);
		$res = array();

		if ($receive['type'] == 'add') {
			$this->users->get($k)->setUsername($receive['username']);
			$res = array(
				'add' => true,
				'content' => '用户' . $receive['username'] . '加入了聊天室',
				'users' => $this->getUsers()
			);
		} else if ($receive['type'] == 'long') {
			$res['content'] = $receive['username'] . ": " . $receive['message'];

			$repository = $this->em->getRepository('AppBundle:Message');
			$repository->create($receive['username'], 'all', $res['content']);
		}

		$key = 'all';
		$msg = $this->pack($res);



		$this->sendMessage($k, $msg, $key);
	}

	/**
	 * 获取用户名字的列表
	 * @return array
	 */
	public function getUsers()
	{
		$ar = array();

		/**
		 * @var ClientUser $v
		 */
		foreach ($this->users->toArray() as $k => $v) {
			$ar[] = $v->getUsername();
		}

		return $ar;
	}

	/**
	 * 被send()方法调用，默认向所有用户发送消息
	 * @param $from        int      规定被发送人的id
	 * @param $str      string   发送的字符串
	 * @param $to      string   规定向哪个用户写入消息
	 */
	public function sendMessage($from, $str, $to = 'all')
	{
		if ($to == 'all') {
			/**
			 * @var ClientUser $v
			 */
			foreach ($this->users->toArray() as $v) {
				socket_write($v->getSocket(), $str, strlen($str));     //向每一个client写入消息
			}
		} else {
			if ($from != $to)
				socket_write($this->users->get($to)->getSocket(), $str, strlen($str));
		}
	}

	/***
	 * 发送2 ，用于发送退出信息
	 * @param $username
	 * @param $k
	 */
	public function sendDisconnectMessage($username, $k)
	{
		$response = array(
			'remove' => true,
			'leave' =>true,
			'removeKey' => $k,
			'content' => $username . '退出聊天室',
			'users' => $this->getUsers()
		);
		$str = $this->pack($response);
		$this->sendMessage(false, $str, 'all');
	}

	/**
	 * 日志记录
	 * @param $str
	 */
	public function e($str)
	{
		$path = dirname(__FILE__) . '/log.txt';
		$str = $str . "\n";
		error_log($str, 3, $path);
		echo iconv('utf-8', 'gbk//IGNORE', $str);
	}
}