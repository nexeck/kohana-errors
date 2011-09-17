<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Set the exception handler to use the Error module
 */
set_exception_handler(array('Error', 'handler'));

/**
 * ADD THE FOLLOWING LINES TO THE BOOTSTRAP BEFORE THE CALL TO Kohana::init()
 * -----------------------------------------------------------------------------
 * // Register the Error module's shutdown function before Kohana's
 * register_shutdown_function(function(){Error::shutdown_handler();});
 */
