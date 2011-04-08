<?php
/**************************************************
*  Created:  2011-4-7
*
*  控制器
*
*  @Bct (C)1996-2099 Bct Inc.
*  @Author nopsky <cnnopsky@gmail.com>
*
***************************************************/

class base {
	
	/**
	 * 
	 * 用户信息
	 * @var array
	 */
	public $user = array();
	
	/**
	 * 
	 * 时间戳
	 * @var int
	 */
	public $time = 0;
	
	/**
	 * 
	 * 访问IP
	 * @var string
	 */
	public $ip = '';
	
	/**
	 * 
	 * 用户组信息
	 * @var array
	 */
	public $group = array();
	
	/**
	 * 
	 * 页面标题
	 * @var string
	 */
	public $title = '';
	
	/**
	 * 
	 * 页面关键字
	 * @var string
	 */
	public $keyword = '';
	
	/**
	 * 
	 * 模板变量
	 * @var array
	 */
	public $tplvar = array();
	
	public $model = '';
	
	public function __construct(){
		
	}
	
	/**
	 * 
	 * 初始化信息
	 */
	public function init() {
		$this->initParam();
		$this->initUser();
	}
	
	/**
	 * 初始化常用变量
	 * 
	 */
	public function initParam() {
		$this->tplvars['title'] = $this->title;
		$this->tplvars['keyword'] = $this->keyword;
	}
	
	public function initUser() {
		$this->user = array('uid'=>0, 'group'=>0);
	}
    
	public function __get($var) {
		if($var == 'ip') {
			$this->ip = $this->getIp();
			return $this->ip;
		} else if($var == 'time') {
			$this->time = time();
			return $this->time();
		} else if(substr($var, -5) == 'model') {
			$class = substr($var,0,-5);
			require(APP_M.'/'.M_PATH.$class.'.php');
			return new $class();
		}
	}
	
    /**
     * 获取用户IP
     *
     * @param string $default
     * @return string
     */
    public function getIp($default = '0.0.0.0')
    {
        $pattern = '/^((\d|[1-9]\d|1\d{2}|2[0-4]\d|25[0-5])\.){3}(\d|[1-9]\d]|1\d{2}|2[0-4]\d|25[0-5])$/';

        $keys = array('HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR');

        foreach ($keys as $key) {
            if (empty($_SERVER[$key])) continue;
		    $ips = explode(',', $_SERVER[$key], 1);
		    if (preg_match($pattern, $ips[0])) return $ips[0];
		}

        return $default;
    }
	
	/**
	 * 
	 * 设置页面标题
	 * @param string $title
	 */
	public function setTitle($title) {
		$this->title = $title;
	}
	
	/**
	 * 
	 * 设置页面关键字
	 * @param string $keyword
	 */
	public function setKeyword($keyword) {
		$this->keyword = $keyword;
	}
	
	/**
	 * 模板显示
	 * 
	 * @return return_type
	 */
	public function tpl($file) {
		require(APP_L.'/template.class.php');
		$tpl = new template();
		extract($tplvars);
		include $tpl->template($file, M_PATH);
	}

	/**
	 * 跳转函数
	 *
	 * @param string $url 需要跳转的目标URL
	 * @param string $msg 如果需要在页面里提示消息
	 * @return unknown
	 */
	public function redirect($url = '/', $msg = ''){
		if (headers_sent()){
			return $this->go($url, $msg);
		}
		header("Location: $url");
		exit;
	}

	/**
	 * HTML跳转
	 *
	 * @param string $path 需要跳转到的目标URL
	 * @return bool
	 */
	public function go($url = '', $msg = ''){
		$html = '<html>'.
				'<head>'.
				'<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">'.
				'<script type="text/javascript">'.
				'<!--'.
				'var url = "'.$url.'";'.
				'var msg = "'.$msg.'";'.
				"if (msg != '') alert(msg);".
				"if (url == '') window.history.back();".
				'else window.location.href = url;'.
				'-->'.
				'</script>'.
				'</head>'.
				'<body></body>'.
				'</html>';
		echo $html;
		exit;
	}

	/**
	 * 加解密函数
	 * @param  string $string, string $operation, string $key, int $expiry
	 * @return string
	 */
    public  function authcode($string, $operation = 'DECODE', $key = 'bctkey', $expiry = 0) {
        $ckey_length = 4; // 随机密钥长度 取值 0-32;
        // 加入随机密钥，可以令密文无任何规律，即便是原文和密钥完全相同，加密结果也会每次不同，增大破解难度。
        // 取值越大，密文变动规律越大，密文变化 = 16 的 $ckey_length 次方
        // 当此值为 0 时，则不产生随机密钥
        
        $key = md5($key);
        $keya = md5(substr($key, 0, 16));
        $keyb = md5(substr($key, 16, 16));
        $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length) : substr(md5(microtime()), -$ckey_length)) : '';
        
        $cryptkey = $keya.md5($keya.$keyc);
        $key_length = strlen($cryptkey);
        
        $string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;
        $string_length = strlen($string);
        
        $result = '';
        $box = range(0, 255);
        
        $rndkey = array();
        for ($i = 0; $i <= 255; $i++) {
            $rndkey[$i] = ord($cryptkey[$i % $key_length]);
        }
        
        for ($j = $i = 0; $i < 256; $i++) {
            $j = ($j + $box[$i] + $rndkey[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }
        
        for ($a = $j = $i = 0; $i < $string_length; $i++) {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
        }
        
        if ($operation == 'DECODE') {
            if ((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {
                return substr($result, 26);
            } else {
                return '';
            }
        } else {
            return $keyc.str_replace('=', '', base64_encode($result));
        }
    }
    
    /**
     * 获取参数
     * @param string $k, string $var
     * @return $mix
     */
	public function getgpc($k, $var = 'G') {
		switch($var) {
			case 'G': $var = &$_GET; break;
			case 'P': $var = &$_POST; break;
			case 'C': $var = &$_COOKIE; break;
			case 'R': $var = &$_REQUEST; break;
			case 'S': $var = &$_SERVER; break;
		}
		return isset($var[$k]) ? $var[$k] : NULL;
	}
}
?>