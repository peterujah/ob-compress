<?php
/**
 * OBCompress - A simple php class to compress output buffer
 * @author      Peter Chigozie(NG) peterujah
 * @copyright   Copyright (c), 2021 Peter(NG) peterujah
 * @license     MIT public license
 */
namespace Peterujah\NanoBlock;
 class OBCompress{
    // Constants for content types
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
	* Array to hold response headers
	* @var array $headers
	*/
    private $headers;

    /** 
	*  Gzip compression status
	* @var bool $gzip
	*/
    private $gzip; 

	/** 
	*  ignore User Abort
	* @var bool $ignoreUserAbort
	*/
	private $ignoreUserAbort = true;

    private $ignoreCodeblock = false;

	/** 
	* holds html expiry time offset 7 days
	* @var string $cacheExpiry
	*/
	private $cacheExpiry = 60 * 60 * 24 * 7;

	/** 
	* holds path to save php to html cached files
	* @var string $cacheDir
	*/
	private $cacheDir = __DIR__ . "/../ob_optimizer_caches";


    // Regular expression patterns for content stripping
    private const OPTIONS = array(
        "find" => array(
            '/\>[^\S ]+/s',          // Strip whitespace after HTML tags
            '/[^\S ]+\</s',          // Strip whitespace before HTML tags
            '/(\s)+/s',              // Strip excessive whitespace
            '/<!--(.*)-->/Uis',      // Strip HTML comments
            '/[[:blank:]]+/'         // Strip blank spaces
        ),
        "replace" => array(
            '>',
            '<',
            '\\1',
            '',
            ' '
        ),
        "line" => array(
            //'/[\n\r\t]+/'
            "\n",
            "\r",
            "\t"
        )
    );
    
 
    /**
     * Class constructor.
     * Initializes default settings for the response headers and cache control.
     */
    public function __construct() {
        $this->headers = array(
            'Content-Encoding' => '',
            'Content-Type' => 'charset=utf-8',
            'Cache-Control' => 'no-store',
            'Expires' => gmdate("D, d M Y H:i:s", time() + 60 * 60 * 30) . ' GMT',
            'Content-Length' => 0,
            'Content-Language' => 'en',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'deny',
            'X-XSS-Protection' => '1; mode=block',
            'Vary' => 'Accept-Encoding',
            'Connection' => 'close',
        );
        $this->gzip = true;
    }

    /**
     * Enable or disable Gzip compression.
     *
     * @param bool $gzip Enable Gzip compression (true) or disable it (false).
     * @return OBCompress Returns the class instance for method chaining.
     */
    public function useGzip(bool $gzip): OBCompress {
        $this->gzip = $gzip;
        return $this;
    }

    /**
     * Set the expiration offset for the Cache-Control header.
     *
     * @param int $offset Cache expiration offset in seconds.
     * @return OBCompress Returns the class instance for method chaining.
     */
    public function setExpires(int $offset): OBCompress {
        $this->headers['Expires'] = gmdate("D, d M Y H:i:s", time() + $offset) . ' GMT';
        return $this;
    }

	/**
     * Set the expiration offset for the Cache-Control header.
     *
     * @param int $expire Cache expiration offset in seconds.
     * @return OBCompress Returns the class instance for method chaining.
     */
    public function setHtmlExpires(int $expire): OBCompress {
		$this->cacheExpiry = $expire;
        return $this;
    }

    /**
     * Set the Cache-Control header.
     *
     * @param string $cacheControl Cache-Control header value.
     * @return OBCompress Returns the class instance for method chaining.
     */
    public function setCacheControl(string $cacheControl): OBCompress {
        $this->headers['Cache-Control'] = $cacheControl;
        return $this;
    }

	/**
     * sets ignore user abort
     *
     * @param bool $ignore Cache-Control header value.
     * @return OBCompress Returns the class instance for method chaining.
     */
	public function setIgnoreUserAbort($ignore){
		$this->ignoreUserAbort = $ignore;
		return $this;
	}

    /**
     * sets ignore user code block
     *
     * @param bool $ignore
     * @return OBCompress Returns the class instance for method chaining.
     */
	public function setIgnoreCodeblock($ignore){
		$this->ignoreCodeblock = $ignore;
		return $this;
	}

	/**
     * sets file path
     *
     * @param string|dir|path $cacheDir path to save cache
     * @return OBCompress Returns the class instance for method chaining.
     */
	public function setOptimizerCachePath($cacheDir) {
        $this->cacheDir = $cacheDir;
		return $this;
    }

    /**
     * Compresses the buffer content and adds necessary headers to optimize the response.
     *
     * @param string|array|object $data The content to compress (can be an array or object for JSON response).
     * @param string $contentType The expected content type for the response.
     * @return string The compressed content for output.
     */
    public function compress($data, string $contentType): string {
        $content = ($contentType === self::JSON) ? json_encode($data, true) : $data;
        $minifiedContent = $this->ignoreCodeblock ? self::minifyIgnoreCodeblock($content) : self::minify($content);
        if ($this->gzip && !empty($_SERVER['HTTP_ACCEPT_ENCODING']) && strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false) {
            $this->headers['Content-Encoding'] = 'gzip';
            $minifiedContent = gzencode($minifiedContent, 9);
        }
        $this->headers['Content-Length'] = strlen($minifiedContent);
        $this->headers['Content-Type'] = $contentType . ' ' . $this->headers['Content-Type'];

        foreach ($this->headers as $header => $value) {
            header("$header: $value");
        }

        return $minifiedContent;
    }

    /**
     * Sends the response with the specified content type and status code.
     *
     * @param string|array|object $body The content body to be sent in the response.
     * @param int $statusCode The HTTP status code to be sent in the response.
     * @param string $contentType The expected content type for the response.
     */
    public function with($body, int $statusCode, string $contentType) {
        set_time_limit(0);
        ignore_user_abort($this->ignoreUserAbort);
        ob_end_clean();
        echo $this->compress($body, $contentType);
        if ($statusCode) {
            http_response_code($statusCode);
        }
        if (ob_get_length() > 0) {
            ob_end_flush();
        }
    }

    /**
     * Minify and save output as html, and serve html on next request
     *
     * @param string|array|object $body The content body to be sent in the response.
     */
	public function withOptimizer($body) {
		$optimizer = new \Peterujah\NanoBlock\OBOptimizer($this->cacheExpiry, $this->cacheDir);
		$cacheFile = $optimizer->getCacheLocation();
	
		if ($optimizer->isCacheValid($cacheFile)) {
			$optimizer->loadFromCache($cacheFile);
			return;
		}
	
        $minifiedContent = $this->ignoreCodeblock ? self::minifyIgnoreCodeblock($body) : self::minify($body);
		set_time_limit(0);
		ignore_user_abort($this->ignoreUserAbort);
		echo $optimizer->saveToCache($cacheFile, $minifiedContent);
		ob_end_flush();
	}

    /**
     * Send the output in HTML format.
     *
     * @param string|array|object $body The content body to be sent in the response.
     */
    public function html($body) {
        $this->with($body, 200, self::HTML);
    }

    /**
     * Send the output in text format.
     *
     * @param string|array|object $body The content body to be sent in the response.
     */
    public function text($body) {
        $this->with($body, 200, self::TEXT);
    }

    /**
     * Send the output in JSON format.
     *
     * @param string|array|object $body The content body to be sent in the response.
     */
    public function json($body) {
        $this->with($body, 200, self::JSON);
    }

    /**
     * Send the output based on the specified content type.
     *
     * @param string|array|object $body The content body to be sent in the response.
     * @param string $contentType The expected content type for the response.
     */
    public function run($body, string $contentType = self::HTML) {
        $this->with($body, 200, $contentType);
    }

    /**
     * End output buffering and send the response.
     *
     * @param string $contentType The expected content type for the response.
     */
    public function end(string $contentType = self::HTML) {
        $this->with(ob_get_contents(), 200, $contentType);
    }

    /**
     * Start output buffering and minify the content by removing unwanted tags and whitespace.
    */
    public function startMinify() {
        if($this->ignoreCodeblock){
            ob_start(['self', 'minifyIgnoreCodeblock']);
        }else{
            ob_start(['self', 'minify']);
        }
    }
    
    /**
     * Minify the given buffer content by removing unwanted tags and whitespace.
     *
     * @param string $buffer The content output buffer to minify.
     * @return string The minified content.
     */
    public static function minify($buffer): string {
        $minified_buffer = preg_replace(
            self::OPTIONS["find"],
            self::OPTIONS["replace"],
            str_replace(self::OPTIONS["line"], '', $buffer)
        );
        return trim(preg_replace('/\s+/', ' ', $minified_buffer));
    }

    /**
     * @deprecated This method is deprecated. Use the start() method instead.
     * Call ob_start(), ob_start(['\Peterujah\NanoBlock\OBCompress', 'minify']); 
     * or ob_start(['\Peterujah\NanoBlock\OBCompress', 'minifyIgnoreCodeblock']);
     * Start output buffering and minify the content by removing unwanted tags and whitespace.
     */
    public static function start($minify = false) {
        ob_start($minify ? ['self', 'minify'] : null);
    }
    
    /**
     * Minify the given buffer content by removing unwanted tags and whitespace.
     * But ignore html <pre><code> block
     * @param string $buffer The content output buffer to minify.
     * @return string The minified content.
     */
    public static function minifyIgnoreCodeblock($buffer): string {
        $ignored_blocks = [];
        $buffer = preg_replace_callback(
            '/<pre><code>([\s\S]*?)<\/code><\/pre>/i',
            function ($matches) use (&$ignored_blocks) {
                $ignored_blocks[] = $matches[1];
                return '<!--OB_COMPRESS_IGNORED_BLOCK-->';
            },
            $buffer
        );
    

        $minified_buffer = preg_replace_callback(
            '/<!--OB_COMPRESS_IGNORED_BLOCK-->/',
            function () use (&$ignored_blocks) {
                $block = array_shift($ignored_blocks);
                $replacement = preg_replace('/class="language-(.*?)"/i', 'class="$1"', $block);
                return '<pre><code ' . $replacement . '>' . $block . '</code></pre>';
            },
            preg_replace(
                self::OPTIONS["find"],
                self::OPTIONS["replace"],
                $buffer
            )
        );
    
        return $minified_buffer;
    }
    
}
