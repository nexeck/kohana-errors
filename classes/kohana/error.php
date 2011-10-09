<?php defined('SYSPATH') or die('No direct script access.');

class Kohana_Error {

	/**
	 * Error Type
	 *
	 * @var string
	 */
	public $type;

	/**
	 * Error Code
	 *
	 * @var int
	 */
	public $code;

	/**
	 * Error Message
	 *
	 * @var null
	 */
	public $message;

	/**
	 * Error File
	 *
	 * @var string
	 */
	public $file;

	/**
	 * Error Line
	 *
	 * @var int
	 */
	public $line;

	/**
	 * Error Text
	 *
	 * @var string
	 */
	public $text;

	/**
	 * Error Trace
	 *
	 * @var array
	 */
	public $trace = array();

	/**
	 * Text to Display
	 *
	 * @var string
	 */
	public $display;

	/**
	 * Exception
	 *
	 * @var Exception
	 */
	public $exception;

	/**
	 * Initial Request
	 *
	 * @var Request
	 */
	private $request_initial;

	/**
	 * Replaces Kohana's `Kohana::exception_handler()` method. This does the
	 * same thing, but also adds email functionality and the ability to perform
	 * an action in response to the exception. These actions and emails are
	 * customizable per type in the config file for this module.
	 *
	 * @uses		Kohana::exception_text
	 * @param \Exception $e
	 * @internal param \exception $object object
	 * @return	boolean
	 */
	public static function handler(Exception $e)
	{
		try
		{
			$error = new Error();

			// Get the exception information
			$error->exception = $e;
			$error->type = get_class($e);
			$error->code = $e->getCode();
			$error->message = $e->getMessage();
			$error->file = $e->getFile();
			$error->line = $e->getLine();
			$error->request_initial = Request::initial();

			// Create a text version of the exception
			$error->text = Kohana_Exception::text($e);

			if (Kohana::$is_cli)
			{
				// Display the text of the exception
				echo "\n{$error->text}\n";
			}

			// Get the exception backtrace
			$error->trace = $e->getTrace();

			if ($e instanceof ErrorException)
			{
				if (version_compare(PHP_VERSION, '5.3', '<'))
				{
					// Workaround for a bug in ErrorException::getTrace() that exists in
					// all PHP 5.2 versions. @see http://bugs.php.net/bug.php?id=45895
					for ($i = count($error->trace) - 1;$i > 0;--$i)
					{
						if (isset($error->trace[$i - 1]['args']))
						{
							// Re-position the args
							$error->trace[$i]['args'] = $error->trace[$i - 1]['args'];

							// Remove the args
							unset($error->trace[$i - 1]['args']);
						}
					}
				}
			}

			if (!headers_sent() and (Kohana::$is_cli === false))
			{
				// Make sure the proper content type is sent with a 500 status
				header('Content-Type: text/html; charset=' . Kohana::$charset, true, 500);
			}

			// Get the contents of the output buffer
			$error->display = $error->render();

			// Log the error
			$error->log();

			// Email the error
			$error->email();

			// Respond to the error
			$error->action();

			return true;
		}
		catch (Exception $e)
		{
			// Log an error.
			if (is_object(Kohana::$log))
			{
				// Create a text version of the exception
				$error = Kohana_Exception::text($e);

				// Add this exception to the log
				Kohana::$log->add(Log::ERROR, $error);

				// Make sure the logs are written
				Kohana::$log->write();
			}

			// Clean the output buffer if one exists
			ob_get_level() and ob_clean();

			// Display the exception text
			header('HTTP/1.1 500 Internal Server Error');
			echo "Unknown Error - Exception thrown in Error::handler()";

			// Exit with an error status
			exit(1);
		}
	}

	/**
	 * Replace Kohana's `Kohana::shutdown_handler()` method with one that will
	 * use our error handler. This is to catch errors that are not normally
	 * caught by the error handler, such as E_PARSE.
	 *
	 * @uses		Error::handler
	 * @return	void
	 */
	public static function shutdown_handler()
	{
		if (Kohana::$errors and $error = error_get_last() and (error_reporting() & $error['type']))
		{
			// If an output buffer exists, clear it
			ob_get_level() and ob_clean();

			// Fake an exception for nice debugging
			Error::handler(new ErrorException($error['message'], $error['type'], 0, $error['file'], $error['line']));

			// Shutdown now to avoid a "death loop"
			exit(1);
		}
	}

	/**
	 * Retrieves the config settings for the exception type, and cascades down
	 * to the _default settings if there is nothing relavant to the type.
	 *
	 * @param	 string	$key			The config key
	 * @param	 mixed	 $default	A default value to return
	 * @return	mixed
	 */
	public function config($key, $default = null)
	{
		$config = Kohana::$config->load('error.' . $this->type . ':' . $this->code . '.' . $key);
		$config = !is_null($config) ? $config : Kohana::$config->load('error.' . $this->type . '.' . $key);
		$config = !is_null($config) ? $config : Kohana::$config->load('error._default.' . $key);
		return !is_null($config) ? $config : $default;
	}

	/**
	 * Renders an error with a view file. The view library is not used because
	 * there is a chance that it will fail within this context.
	 *
	 * @param	 string	$view	The view file
	 * @return	string
	 */
	public function render($view = 'kohana/error')
	{
		// Start an output buffer
		ob_start();

		// Import error variables into View's scope
		$error = get_object_vars($this);
		unset($error['display']);
		extract($error);

		// Include the exception HTML
		include Kohana::find_file('views', $view);

		// Get the contents of the output buffer
		return ob_get_clean();
	}

	/**
	 * Performs the logging is enabled
	 */
	public function log()
	{
		if (($this->config('log', false) or (($this->config('cli.log', true) === true) and Kohana::$is_cli)) and is_object(Kohana::$log))
		{
			Kohana::$log->add(Log::ERROR, $this->text);
		}
	}

	/**
	 * Sends the email if enabled
	 *
	 * @return	void
	 */
	public function email()
	{
		$email_available = (class_exists('Email') and method_exists('Email', 'send'));
		if (!$email_available)
		{
			throw new Exception('The email functionality of the Error module requires an Email module.');
		}

		if ((($this->config('email', false) === false) and (Kohana::$is_cli === false)) or (Kohana::$is_cli and ($this->config('cli.log', true) === false)))
		{
			return;
		}

		$content = $this->display;

		if (($email_view = $this->config('email.view', null)) !== null)
		{
			$content = $this->render($email_view);
		}

		$email = Email::factory();

		$email->from($this->config('email.from'));
		$email->to($this->config('email.to'));

		$email->subject(getenv('KOHANA_ENV') . ' | ' . $this->type);

		$email->attach_content($content, $this->type . '.html');

		$email->message($this->message, 'text/plain');

		$success = $email->send();

		if (!$success)
		{
			throw new Exception('The error email failed to be sent.');
		}
	}

	/**
	 * Performs the action set in configuration
	 *
	 * @return	boolean
	 */
	public function action()
	{
		if (Kohana::$is_cli and ($this->config('cli.action', true) === false))
		{
			return false;
		}

		$type = '_action_' . $this->config('action.type', null);
		$options = $this->config('action.options', array());
		$this->$type($options);
		return true;
	}

	/**
	 * Redirects the user upon error
	 *
	 * @param	 array	$options	Options from config
	 * @return	void
	 */
	protected function _action_redirect(array $options = array())
	{
		if ($this->code === 'E_PARSE')
		{
			echo '<p><strong>NOTE:</strong> Cannot redirect on a parse error, because it might cause a redirect loop.</p>';
			echo $this->display;
			return;
		}

		$hint_available = (class_exists('Hint') and method_exists('Hint', 'set'));
		$message = Arr::get($options, 'message', false);
		if ($hint_available and $message)
		{
			Hint::set(HINT::ERROR, $message);
		}

		$url = Arr::get($options, 'url');
		if (strpos($url, '://') === false)
		{
			// Make the URI into a URL
			$url = URL::site($url, true);
		}
		header("Location: $url", true);
		exit;
	}

	/**
	 * Displays the error
	 *
	 * @param	 array	$options	Options from config
	 * @return	void
	 */
	protected function _action_display(array $options = array())
	{
		if (((Kohana::$environment > Kohana::STAGING) and ($this->request_initial->query('show_error') == 'true')) or ($this->request_initial->query('display_token') == $this->config('display_token', false)))
		{
			Kohana_Exception::handler($this->exception);
			exit(1);
		}
		$view = Arr::get($options, 'view', 'errors/_default');

		$this->display = $this->render($view);

		echo $this->display;
	}

	/**
	 * Performs a callback on the error
	 *
	 * @param	 array	$options	Options from config
	 * @return	void
	 */
	protected function _action_callback(array $options = array())
	{
		$callback = Arr::get($options, 'callback');
		@list($method,) = Arr::callback($callback);
		if (is_callable($method))
		{
			call_user_func($method, $this);
		}
	}

	/**
	 * CatchAll for actions. Do nothing.
	 *
	 * @param	string	$method
	 * @param	array	 $args
	 */
	public function __call($method, $args) { }

	/**
	 * Display Error Callback
	 *
	 * @param \Error|object $error The error object
	 */
	public static function example_callback(Error $error)
	{
		$error->display = '<p>THERE WAS AN ERROR!</p>';
		echo $error->display;
	}

} // End Kohana_Error
