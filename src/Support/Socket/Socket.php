<?php
/**
 * Created by PhpStorm.
 * User: Jake
 * Date: 2016/12/23
 * Time: 0:09
 */

namespace Support\Socket;


class Socket
{
	public $sockets;    //所有连接对象
	public $users;      //用户列表数组
	public $master;     //socket对象

	public function __construct($address, $port)
	{
		$this->master = $this->WebSocket($address, $port);
		$this->sockets = array('s' => $this->master);
	}


	function run()
	{
		$this->e('server is running...');
		while (true) {
			echo "hello,world";
			$changes = $this->sockets;
			$write = null;
			$except = NULL;
			socket_select($changes, $write, $except, NULL);     //
			foreach ($changes as $sock) {
				if ($sock == $this->master) {                   //查找到当前的客户端
					$client = socket_accept($this->master);
					$this->e('已经接受socket连接: ' . $this->master);
					$this->sockets[] = $client;
					$this->users[] = array(
						'socket' => $client,
						'hasHandShake' => false,
					);
				} else {
					$len = socket_recv($sock, $buffer, 2048, 0);
					if ($len == 0) {
						//$this->e("socket_read() failed reason: " . $len . "\n");
						$this->e("socket read failed !");
					}
					$k = $this->search($sock);
					if ($len < 7) {                         //如果$len的长度小于7，那么关闭这个客户端的链接
						$name = $this->users[$k]['username'];
						$this->close($sock);
						$this->send2($name, $k);
						continue;
					}
					if (!$this->users[$k]['hasHandShake']) {      //尚未握手，先进行握手
						$this->handShake($k, $buffer);
						//parse_str($buffer,$g);
						//$this->users[$k]['username']  = $g['username'];
					} else {                              //已经握手，开始双工通信
						$buffer = $this->uncode($buffer);
						$this->send($k, $buffer);
					}
				}
			}

		}

	}

	/**
	 * 寻找$this->sockets中是否含有$sock[socket对象]，如果存在，那么删除他
	 * @param $sock     resource    socket对象
	 */
	function close($sock)
	{
		$k = array_search($sock, $this->sockets);
		socket_close($sock);
		unset($this->sockets[$k]);
		unset($this->users[$k]);
		$this->e("a connection is closed,key:$k close");
	}

	/**
	 * 在用户数组中查找一个socket对象，如果存在，则返回属于该对象的用户的键
	 * users是一个当前所有用户的数组，二维数组
	 * @param $sock
	 * @return bool|int|string
	 */
	function search($sock)
	{
		foreach ($this->users as $k => $v) {
			if ($sock == $v['socket'])
				return $k;
		}
		return false;
	}

	/**
	 * 创建并且监听一个socket服务器,
	 *
	 * @param $address
	 * @param $port
	 * @return resource 返回当前当前socket服务对象
	 */
	function WebSocket($address, $port)
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
	 * buffer应该是客户端的连接信息，$k是客户端标识[键]
	 *
	 * @param $k
	 * @param $buffer
	 * @return bool
	 */
	function handShake($k, $buffer)
	{
		$buf = substr($buffer, strpos($buffer, 'Sec-WebSocket-Key:') + 18);    //返回buffer的子字符串
		$key = trim(substr($buf, 0, strpos($buf, "\r\n")));       //返回buffer的第一行

		$new_key = base64_encode(sha1($key . "258EAFA5-E914-47DA-95CA-C5AB0DC85B11", true));   //得到加密的字符串

		$new_message = "HTTP/1.1 101 Switching Protocols\r\n";
		$new_message .= "Upgrade: websocket\r\n";
		$new_message .= "Sec-WebSocket-Version: 13\r\n";
		$new_message .= "Connection: Upgrade\r\n";
		$new_message .= "Sec-WebSocket-Accept: " . $new_key . "\r\n\r\n";

		socket_write($this->users[$k]['socket'], $new_message, strlen($new_message));
		$this->users[$k]['hasHandShake'] = true;
		$this->e('握手成功！');
		return true;

	}

	/**
	 * 数据包解码
	 * @param $str
	 * @return bool|string
	 */
	function uncode($str)
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
	function code($msg)
	{
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
	function ord_hex($data)
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
	 * @param $msg  string  是一个形如 query string的字符串 例如 message=what the fuck&type=add
	 */
	function send($k, $msg)
	{
		if(empty($msg))
			return;
		$this->e('接收到的字符串为：' . $msg);
		parse_str($msg, $g);  //parse_str() 函数把查询字符串msg解析到变量数组g中。
		//$g = $this->parse_query($msg);
		//var_dump($g);
		$ar = array();
		if ($g['type'] == 'add') {                      //maybe 第一个进入房间，然后服务器第一次返回欢迎消息
			$this->users[$k]['username'] = $g['username'];
			$ar['add'] = true;
			$ar['content'] = '用户' . $g['username'] . '加入了聊天室';
			$ar['users'] = $this->getusers();
			$key = 'all';
		} else if ($g['type'] == 'long') {
			$ar['content'] = $g['username'] . ": " . $g['message'];
			$key = 'all';
		} else {
			$key = 'all';       //默认向所有用户发送消息
		}
		$msg = json_encode($ar);
		//var_dump("will send message : " . $msg);
		$msg = $this->code($msg);
		$this->send1($k, $msg, $key);
		//socket_write($this->users[$k]['socket'],$msg,strlen($msg));
	}

	/**
	 * 获取用户名字的列表
	 * @return array
	 */
	function getusers()
	{
		$ar = array();
		foreach ($this->users as $k => $v) {
			$ar[$k] = $v['username'];
		}
		return $ar;
	}

	/**
	 * 被send()方法调用，默认向所有用户发送消息
	 * @param $k        int      规定被发送人的id
	 * @param $str      string   发送的字符串
	 * @param $key      string   规定向哪个用户写入消息
	 */
	function send1($k, $str, $key = 'all')
	{
		if ($key == 'all') {
			foreach ($this->users as $v) {
				socket_write($v['socket'], $str, strlen($str));     //向每一个client写入消息
			}
		} else {
			if ($k != $key)
				socket_write($this->users[$k]['socket'], $str, strlen($str));
			socket_write($this->users[$key]['socket'], $str, strlen($str));
		}
	}

	/***
	 * 发送2 ，用于发送退出信息
	 * @param $username
	 * @param $k
	 */
	function send2($username, $k)
	{
		$ar['remove'] = true;
		$ar['removekey'] = $k;
		$ar['content'] = $username . '退出聊天室';
		$str = $this->code(json_encode($ar));
		$this->send1(false, $str, 'all');
	}

	/**
	 * 日志记录
	 * @param $str
	 */
	function e($str)
	{
		$path = dirname(__FILE__) . '/log.txt';
		$str = $str . "\n";
		error_log($str, 3, $path);
		echo iconv('utf-8', 'gbk//IGNORE', $str);
	}

	/**
	 * 解析 查询字符串
	 * @param   $str    string  需要解析的字符串
	 * @return array or false
	 */
	function parse_query($str)
	{
		$result = array();
		$arr = explode('&', $str);
		foreach ($arr as $key => $value) {
			$arr2 = explode('=', $value);
			$result[$arr2[0]] = $arr2[1];
		}
		return $result;
	}
}