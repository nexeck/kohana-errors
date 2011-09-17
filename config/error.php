<?php defined('SYSPATH') or die('No direct script access.');

return array
(
	/**
	 * Default Error Handling Settings
	 */
	'_default' => array
	(
		/**
		 * Display Token
		 */
		'display_token' => 'display_token',

		/**
		 * LOGGING
		 *
		 * If `log` is TRUE, then the error will be logged. If FALSE, then it
		 * will not be logged.
		 */
		'log' => true,

		/**
		 * EMAIL
		 *
		 * If `email` is FALSE no email will be sent.
		 * from/to must be set to an valid Email
		 * view is optional
		 */
		'email' => false,
		// -----------------------------------------------------------------------------
		// EXAMPLE: "email"
		// -----------------------------------------------------------------------------
		//		'email' => array(
		//			'from' => 'noreply@mbeck.org',
		//			'to' => array(
		//				'error@mbeck.org' => 'mBeck Error',
		//			),
		//			'view' => 'kohana/error',
		//		),

		/**
		 * ACTION
		 *
		 * If `action` is not an array or has an invalid or missing type, then
		 * the error will be displayed just like the normal
		 * `Kohana::exception_handler`. If it is an array, then the specified
		 * action will be taken with the options specified.
		 */
		'action' => array
		(
			// -----------------------------------------------------------------------------
			// EXAMPLE: "display"
			// -----------------------------------------------------------------------------
			//			'type'    => 'display',
			//			'options' => array
			//			(
			//				// View used to replace the default error display
			//				'view'     => 'errors/_default',
			//			),

			// -----------------------------------------------------------------------------
			// EXAMPLE: "callback"
			// -----------------------------------------------------------------------------
			//			'type' => 'callback',
			//			'options' => array
			//			(
			//				// Callback to apply to the error (uses `Arr::callback` syntax)
			//				'callback' => 'Error::example_callback',
			//			),

			// -----------------------------------------------------------------------------
			// EXAMPLE: "redirect"
			// -----------------------------------------------------------------------------
			//			'type'    => 'redirect',
			//			'options' => array
			//			(
			//				// This is where the user will be redirected to
			//				'url'     => 'welcome/index',
			//
			//				// The message to be sent as a Notice (requires Notices module)
			//				'message' => 'There was an error which prevented the page you requested from being loaded.',
			//			),
		),
	),
	'HTTP_Exception_404' => array(
		'log' => false,
		'email' => false,
		'action' => array
		(
			'type'    => 'display',
			'options' => array
			(
				// View used to replace the default error display
				'view'     => 'errors/http/404',
			),
		)
	),
);
