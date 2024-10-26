<?php
// 关闭警告
error_reporting(E_ERROR);
error_reporting(E_ALL & ~E_WARNING);
ini_set('display_errors', 'Off');
//ini_set('default_socket_timeout', '60000');
//ini_set('max_input_time', '60000'); max_execution_time nginx有send_timeout设置

set_time_limit(0);

// 打开日志文件
$logFile = fopen("curl_log.txt", "a");
$canWrite = false;

//if( strpos($_SERVER['REQUEST_URI'], "CompressUploadImage") !== false ) {
	// 尝试锁定文件
	if( flock($logFile, LOCK_EX ) ) { // 独占锁定
		// 在这里进行文件读写操作
		$canWrite = true;
	}
// }

// 获取客户端请求数据
$method = $_SERVER['REQUEST_METHOD'];
$clientHeaders = getallheaders();
unset($clientHeaders["Host"]);

/*if( is_uploaded_file($_FILES['file']['tmp_name']) ) {
	 $opts = array(
		'http' => array(
			'method' => 'POST',
			'header' => $clientHeaders["Content-Type"],
			'content' => file_get_contents($_FILES['file']['tmp_name'])
		)
	);
	
	$context = stream_context_create($opts);
	$response = file_get_contents($url, false, $context); 
} else {*/
	$body = file_get_contents('php://input');
/*}*/




// 目标 URL api.shorebird.dev
$targetUrl = "https://api.shorebird.dev".$_SERVER['REQUEST_URI']; // 将此替换为你想转发的 URL


// curl_setopt($ch, CURLOPT_STDERR, $logFile);
// fwrite($logFile, json_encode($clientHeaders) );

// 初始化 cURL
$ch = curl_init($targetUrl);

// 设置 cURL 选项
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method); // 设置请求方法
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // 获取请求结果
curl_setopt($ch, CURLOPT_BINARYTRANSFER, true); // For images, etc.
curl_setopt($ch, CURLOPT_HTTPHEADER, array_map(function($key) use ($clientHeaders) {
    return "$key: " . $clientHeaders[$key];
}, array_keys($clientHeaders)));

curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_USE_SSL, true);
// curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);

curl_setopt($ch, CURLOPT_FORBID_REUSE, true);
curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
curl_setopt($ch, CURLOPT_VERBOSE, 1);
//curl_setopt($ch, CURLOPT_SSLVERSION , CURL_SSLVERSION_TLSv1);


// 设置客户端证书
$certPath = 'cacert.pem'; // PEM文件路径 ？？？ 为何设置 [curl] curl.cainfo= 或 [openssl] openssl.capath= 和重启nginx没起效果？需要kill php??
//curl_setopt($ch, CURLOPT_SSLCERT, "D:/phpstudy_pro/Extensions/php/php7.3.4nts/extras/ssl/cacert.pem");
//curl_setopt($ch, CURLOPT_SSLKEY, $certPath);
curl_setopt($ch, CURLOPT_CAINFO, $certPath );
//curl_setopt( $ch, CURLOPT_SSLCERTTYPE, 'PEM' );


// Pass-through POST requests, too
if (strtoupper($_SERVER['REQUEST_METHOD']) == 'POST' && count($_FILES) > 0 ) {
	
/*
	// Set up the POST request

	// Re-upload POST variables
	$post = array();

	// We'll have to unroll multiple dimensions
	function _unroll_array($source, $state = array(), $keypath = '') {
		$add_to_state = array();
		foreach ($source as $key => $val) {
			if ($keypath == '') {
				$new_key = $key;
			} else {
				$new_key = $keypath . "[$key]";
			}
			if (is_array($val)) {
				$add_to_state = array_merge(
					$add_to_state,
					_unroll_array($val, array(), $new_key)
				);
			} else {
				// Move on.
				$add_to_state[$new_key] = $val;
			}
		}
		return array_merge($state, $add_to_state);
	}

	$body = _unroll_array($_POST);


	foreach ($_FILES as $filekey => $contents) {
		if (is_array($contents['name'])) {
			// We have a multi-level file upload
			foreach ($contents['name'] as $key => $name) {
				if ($contents['error'][$key] == 0) {
					$newtmp = 'E:/1024/test/tmp/' . $name;
					if (move_uploaded_file($contents['tmp_name'][$key], $newtmp)) {
						$temps[] = $newtmp;
						$body["{$filekey}[{$key}]"] = '@' . $newtmp / *. ";type={$contents['type'][$key]}"* /;
					}
				}
			}
		}
		if ($contents['error'] == 0) {
			$newtmp = 'E:/1024/test/tmp/' . $contents['name'];
			if (move_uploaded_file($contents['tmp_name'], $newtmp)) {
				$temps[] = $newtmp;
				$body[$filekey] = '@' . $newtmp / *. ";type={$contents['type']}"* /;
			}
		}
	} */

	// curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
	
	// $post_data = file_get_contents("php://input");

    if (preg_match("/^multipart/", strtolower($_SERVER['CONTENT_TYPE']))) {
        // $delimiter = '-------------' . uniqid();
        // $post_data = build_multipart_data_files($delimiter, $_POST, $_FILES);
		// curl_setopt( $curl, CURLOPT_POSTFIELDS, $post_data );
		
		// $clientHeaders["Content-Type"] = "multipart/form-data; boundary=" . $delimiter;
		unset($clientHeaders["Content-Type"]);
		// $clientHeaders["Content-Length"] = strlen($post_data);
		unset($clientHeaders["Content-Length"]);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array_map(function($key) use ($clientHeaders) {
			return "$key: " . $clientHeaders[$key];
		}, array_keys($clientHeaders)));
		
		// CURLFile: {"file":{"name":"\u65e0\u6807\u9898.png","type":"application\/octet-stream","tmp_name":"C:\\Users\\admin\\AppData\\Local\\Temp\\phpA1DE.tmp","error":0,"size":3833}}
		// Create a CURLFile object
		$cfile = new CURLFile( $_FILES["file"]["tmp_name"], $_FILES["file"]["type"],  $_FILES["file"]["name"]); //

		// var_dump( $cfile  );
		// Assign POST data
		$data = array('file' => $cfile);
		// NOTE: CURLOPT_POSTFIELDS 类型会自动构建multipart/form-data类型的分隔符，body的Content-Length长度计算,以及赋值。 
		// CURLOPT_POSTFIELDS 包含 $_POST, $_FILES两者信息，是否解析到post取决于len、type等
		// 简单通过CURLOPT_POSTFIELDS去转发file_get_contents('php://input')以及附带原样请求头（Content-Type、Content-Length）会出现"OpenSSL SSL_read: SSL_ERROR_SYSCALL, errno 10054 "的错误，且会卡住60s左右
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);  
		
		if( $canWrite ) {
			fwrite($logFile, "\n get php://input " . file_get_contents("php://input") . " \n");
			fwrite($logFile, "\n get _FILES " . json_encode($_FILES) . " \n");
			fwrite($logFile, "\n send body " . json_encode($post_data) . " \n");
			fwrite($logFile, "\n send header " . json_encode(array_map(function($key) use ($clientHeaders) {
				return "$key: " . $clientHeaders[$key];
			}, array_keys($clientHeaders))) . " \n");
		}
    }
	
	// exit(0);
} else {
	curl_setopt($ch, CURLOPT_POSTFIELDS, $body); // 转发请求体（如果有）
}

// 启用响应头的输出
curl_setopt($ch, CURLOPT_HEADER, true);

if( $canWrite )
fwrite($logFile, "\n $method $targetUrl Write req body!!\n". '' . "\n");

// 执行请求
$response = curl_exec($ch);
$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$rsp_header = substr($response, 0, $header_size);
$rsp_body = substr($response, $header_size);
header('Access-Control-Allow-Origin: *');
$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	
// 错误处理
if (curl_errno($ch)) {
    http_response_code($statusCode);
	$error = curl_error($ch);
    echo 'cURL error: ' . $error;
	if( $canWrite )
	fwrite($logFile, "cURL Error: $error \n $response \n");
} else {
    // 获取响应信息并返回
   
    header('Content-Type: ' . curl_getinfo($ch, CURLINFO_CONTENT_TYPE));

	// 将响应头输出给浏览器
	foreach (explode("\r\n", $rsp_header) as $hdr) {
		if (!empty($hdr)) {
			header($hdr);
		}
	}
    http_response_code($statusCode);
    echo $rsp_body;
	
	$info = curl_getinfo($ch);
    // fwrite($logFile, "Request Info:\n" . print_r($info, true) . "\n");
	if( $canWrite )
    fwrite($logFile, "\n $method $targetUrl Response Body:\n" . $response . "\n");
}

// 关闭 cURL
curl_close($ch);

fclose($logFile);
// 操作完成后解锁
if( $canWrite )
flock($logFile, LOCK_UN);


/* function build_multipart_data_files($delimiter, $fields, $files) {
    # Inspiration from: https://gist.github.com/maxivak/18fcac476a2f4ea02e5f80b303811d5f :)
    $data = '';
    $eol = "\r\n";
  
	if( count($fields) ) {
		foreach ($fields as $name => $content) {
			$data .= "--" . $delimiter . $eol
				. 'Content-Disposition: form-data; name="' . $name . "\"".$eol.$eol
				. $content . $eol;
		}
	}

  
    foreach ($files as $name => $content) {
        $data .= "--" . $delimiter . $eol
            . 'Content-Disposition: form-data; name="' . $name . '"; filename="' . $name . '"' . $eol
            . 'Content-Transfer-Encoding: binary'.$eol
            ;
        $data .= $eol;
        $data .= $content . $eol;
    }
    $data .= "--" . $delimiter . "--".$eol;

    return $data;
} */
