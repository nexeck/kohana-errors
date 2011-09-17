# Errors

*Error Handling module for Kohana 3.2*

- **Module Version:** 0.1.0
- **Module URL:** <https://github.com/fo3-nik5/mbeck-errors>
- **Compatible Kohana Version(s):** 3.2

# Authors
- Its a modified Version from [Synapse Studios Error Module](https://github.com/synapsestudios/kohana-errors)

## Description

The Error module allows customization of error handling by overriding Kohana's
default exception handler.  With the module you can configure options for
logging, emailing, and displaying errors.  You can also use different
configuration settings for specific types of errors or exceptions.

## Requirements

- [Email Module](https://github.com/fo3-nik5/email) or any other Email Module
- [Hint Module](https://github.com/fo3-nik5/kohana-hint)
- PHP 5.3+

## Installation

1. Add the following lines to bootstrap.php before the call to Kohana::init()
    register_shutdown_function(function(){Error::shutdown_handler();});
