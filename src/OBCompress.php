<?php 
/**
 * OBCompress - A simple php class to compress output buffer
 * @author      Peter Chigozie(NG) peterujah
 * @copyright   Copyright (c), 2021 Peter(NG) peterujah
 * @license     MIT public license
 */
namespace Peterujah\NanoBlock;

 class OBCompress{
	const JSON = "JSON";
	const TEXT = "TEXT";
	const HTML = "HTML";
	private $encoding;
	private $outputEncoding;
	private $contentType;
	private $gzip;
	private $offset;
	private $options = array(
		"find" => array(
			'/\>[^\S ]+/s',     // strip whitespaces after tags, except space
			'/[^\S ]+\</s',     // strip whitespaces before tags, except space
			'/(\s)+/s',         // shorten multiple whitespace sequences
			'/<!--(.*)-->/Uis',  // Remove HTML comments 
			// '/<!--(.|\s)*?-->/',  // Remove HTML comments before
			'/[[:blank:]]+/'
		),
		"replace" => array(
			 '>',
			'<',
			'\\1',
			 '',
			 ' '
		),
		"line" => array(
			"\n",
			"\r",
			"\t"
		)
	);

	public function __construct() {
		$this->serverEncoding($_SERVER['HTTP_ACCEPT_ENCODING']);
		$this->outputEncoding("charset=utf-8");
		$this->useGzip(true);
		$this->setExpires(60 * 60 * 30);
		$this->setContentType("");
	}

	 /**
	 * set use gzip
	 * @param gz bool
	 */
	public function useGzip($gz){
		$this->gzip = $gz;
		return $this;
	}

	public function setExpires($off){
		$this->offset = $off;
		return $this;
	}

	public function serverEncoding($enc){
		$this->encoding = $enc;
		return $this;
	}

	public function outputEncoding($enc){
		$this->outputEncoding = $enc;
		return $this;
	}

	public function setContentType($ctype){
		$this->contentType = $ctype;
		return $this;
	}

	 public function compress( $data, $type ) {
		$content = ($type == self::JSON ? json_encode($data, true) : $data);
		if ( $this->gzip && strpos($this->encoding, 'gzip' ) !== false ) {
			header( 'Content-Encoding: gzip');
			$content = gzencode( trim( preg_replace( '/\s+/', ' ', $content ) ), 9);
		} else {
			header( "Content-Encoding: none\r\n");
		}
		 
		if(!empty(($type)){
			if($type == self::JSON){
				header( "Content-Type: application/json; {$this->outputEncoding}");
			}else if($type == self::TEXT){
				header( "Content-Type: text/plain; {$this->outputEncoding}");
			}else if($type == self::HTML){
				header( "Content-Type: text/html; {$this->outputEncoding}");
			}else{
				if(!empty($this->contentType)){
				    header( "Content-Type: {$this->contentType}");
				}
			}
		}else{
			if(!empty($this->contentType)){
			  header( "Content-Type: {$this->contentType}");
			}
		}
		header( "Cache-Control: must-revalidate");
		header( "expires: " . gmdate("D, d M Y H:i:s", time() + $this->offset) . " GMT" );
		header( 'Content-Length: ' . ($content != null ? strlen( $content ) : 0) );
		header( 'Content-Language: en');
		header( 'X-Content-Type-Options: nosniff');
		header( 'X-Frame-Options: deny');
		header( 'X-XSS-Protection: 1; mode=block');
		header( 'Vary: Accept-Encoding');
		return $content;
	}

	/**
	 * Close the connection to the browser but continue processing the operation
	 * @param $body
	 */
	public function with($body, $code, $type){
		set_time_limit(0);
		ignore_user_abort(true);
		ob_end_clean();
		echo $this->compress($body, $type);
		header("Connection: close\r\n");
		if(!empty($code)){
			http_response_code($code);
		}
		ob_end_flush();
		//ob_flush();
		//flush();
	}
	
	public function html($body){
	    $this->with($body, 200, self::HTML);
	}

	public function text($body){
		$this->with($body, 200, self::TEXT);
	}

	public function json($body){
		$this->with($body, 200, self::JSON);
	}

	public function run($body, $type = self::HTML){
		$this->with($body, 200, $type);
	}
		   
       public static function start(){
       	 ob_start('self::OBStrip');
       }
		   
       public static function end(){
       	 $this->html(ob_get_contents());
       }

	public static function OBStrip($buffer){
		return preg_replace(
			$this->$options["find"], 
			$this->$options["replace"], 
			str_replace(
				$this->$options["line"],
				'',
				$buffer
			)
		);
	}
}
