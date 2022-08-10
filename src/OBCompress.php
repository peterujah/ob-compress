<?php 
/**
 * OBCompress - A simple php class to compress output buffer
 * @author      Peter Chigozie(NG) peterujah
 * @copyright   Copyright (c), 2021 Peter(NG) peterujah
 * @license     MIT public license
 */
namespace Peterujah\NanoBlock;
 class OBCompress{
	/**
	* holds json content type
	* @var string JSON
	*/
	public const JSON = "application/json;";
	 
	/**
	* holds text content type
	* @var string TEXT
	*/
	public const TEXT = "text/plain;";
	 
	/**
	* holds html content type
	* @var string HTML
	*/
	public const HTML = "text/html;";

	/** 
	* holds content strip setting
	* @var array OPTIONS
	*/
	private const OPTIONS = array(
		"find" => array(
			'/\>[^\S ]+/s',
			'/[^\S ]+\</s',
			'/(\s)+/s',
			'/<!--(.*)-->/Uis',
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
	 
	/**
	* holds default server encoding
	* @var string $encoding
	*/
	private $encoding;
	 
	/**
	* holds expected output encoding type
	* @var string $outputEncoding
	*/
	private $outputEncoding;
	 
	/** 
	* holds expected output content type
	* @var string $$contentType
	*/
	private $contentType;
	
	/** 
	* holds gzip stat
	* @var bool $gzip
	*/
	private $gzip;
	
	/** 
	* holds header expiry time offset
	* @var string $offset
	*/
	private $offset;

	/** 
	* holds header cache control
	* @var string $cacheControl
	*/
	private $cacheControl;
	

	/**
	* Class constructor
	*/
	public function __construct() {
		if(isset($_SERVER['HTTP_ACCEPT_ENCODING'])) {
			$this->serverEncoding($_SERVER['HTTP_ACCEPT_ENCODING']);
		}
		$this->outputEncoding("charset=utf-8");
		$this->useGzip(true);
		$this->setExpires(60 * 60 * 30);
		$this->setCacheControl("must-revalidate");
	}


	/**
	* Sets use gzip state
	* @param bool $gz enabling gzip
	* @return OBCompress $this the class instance
	*/
	public function useGzip($gz){
		$this->gzip = $gz;
		return $this;
	}

	/**
	* Sets the expected expiry offset for header
	* @param string $off expiry offset
	* @return OBCompress $this the class instance
	*/
	public function setExpires($off){
		$this->offset = $off;
		return $this;
	}

	 /**
	 * Sets the default server content encoding type
	 * @param string $enc header content encoding
	 * @return OBCompress $this the class instance
	 */
	public function serverEncoding($enc){
		$this->encoding = $enc;
		return $this;
	}

	 /**
	 * Sets the expected output header content type encoding
	 * @param string $enc header content type encoding
	 * @return OBCompress $this the class instance
	 */
	public function outputEncoding($enc){
		$this->outputEncoding = $enc;
		return $this;
	}

	 /**
	 * Sets the expected output header content type
	 * @param string $ctype header content type
	 * @return OBCompress $this the class instance
	 */
	public function setContentType($ctype){
		$this->contentType = $ctype;
		return $this;
	}

	/**
	 * Sets the expected output header cache control
	 * @param string $ctl header cache control type
	 * @return OBCompress $this the class instance
	 */
	public function setCacheControl($ctl){
		$this->cacheControl = $ctl;
		return $this;
	}


	 /**
	 * Compresses the buffer content and added necessary header to optimize the result
	 * @param string|html|array|text|json $body ob content body
	 * @param string $type the expected content type to ouput
	 * @return html|text|json|array|text-gzip-none
	 */

	 public function compress( $data, $type ) {
		$content = ($type == self::JSON ? json_encode($data, true) : $data);
		if ( $this->gzip && !empty($this->encoding) && strpos($this->encoding, 'gzip' ) !== false ) {
			header( 'Content-Encoding: gzip');
			$content = gzencode( trim( preg_replace( '/\s+/', ' ', $content ) ), 9);
		} else {
			header( "Content-Encoding: none\r\n");
		}
		if(!empty($type)){
			header( "Content-Type: {$type} {$this->outputEncoding}");
		}else if(!empty($this->contentType)){
			header( "Content-Type: {$this->contentType}");
		}
		header( "Cache-Control: {$this->cacheControl}");
		header( "expires: " . gmdate("D, d M Y H:i:s", time() + $this->offset) . " GMT" );
		header( 'Content-Length: ' . ($content != null ? strlen( $content ) : 0) );
		header( 'Content-Language: en');
		header( 'X-Content-Type-Options: nosniff');
		header( 'X-Frame-Options: deny');
		header( 'X-XSS-Protection: 1; mode=block');
		header( 'Vary: Accept-Encoding');
		header( "Connection: close" );
		return $content;
	}

	 
	 /**
	 * Starts connection and closes it to the browser but continue processing the operation
	 * @param string|html|array|text|json $body ob content body
	 * @param int $code the response status code
	 * @param string $type the expected content type to output
	 */
	public function with($body, $code, $type){
		set_time_limit(0);
		ignore_user_abort(true);
		ob_end_clean();
		echo $this->compress($body, $type);
		//header("Connection: close");
		if(!empty($code)){
			http_response_code($code);
		}
		ob_end_flush();
		//ob_flush();
		//flush();
	}

	/**
	* Output output in html format
	* @param string|html|array|text|json $body content body
	*/
	public function html($body){
		$this->with($body, 200, self::HTML);
	}

	 /**
	 * Output output in text format
	 * @param string|html|array|text|json $body content body
	 */
	public function text($body){
		$this->with($body, 200, self::TEXT);
	}

	 /**
	 * Output output in json string format
	 * @param string|html|array|text|json $body content body
	 */
	public function json($body){
		$this->with($body, 200, self::JSON);
	}

	 /**
	 * Output output based on passed content type in second parameter 
	 * @param string|html|array|text|json $body content body
	 * @param string $type the expected content type to output
	 */
	public function run($body, $type = self::HTML){
		$this->with($body, 200, $type);
	}
	
	/**
	 * Ends output buffering 
	 * @param string $type expected content output type
	*/
	public function end($type = self::HTML){
		$this->with(ob_get_contents(), 200, $type);
	}

    /**
	 * Strips unwanted tags in document page
	 * Such as comment and newlines
	 * @param string|html|array|text|json $buffer content output buffer
	 * @returns html|text preg_replace
	 */
    public static function minify($buffer){
        return preg_replace(
			self::OPTIONS["find"], 
			self::OPTIONS["replace"], 
			str_replace(
				self::OPTIONS["line"],
				'',
				$buffer
			)
		);
    }

	/**
	 * @depreciated
	 * Strips unwanted tags in document page
	 * Such as comment and newlines
	 * @param string|html|array|text|json $buffer content output buffer
	 * @returns html|text preg_replace
	 */
	public static function ob_strip($buffer){
		return self::minify($buffer);
    }

	/**
	 * Starts output buffing and stript unwanted tags in document
	 */
	public static function start(){
		ob_start('self::minify');
   }
}
