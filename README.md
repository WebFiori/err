# Errors and Exceptions Handler Component of WebFiori Framework

A library for handling PHP errors and exceptions in a better way.

<p align="center">
  <a target="_blank" href="https://github.com/WebFiori/err/actions/workflows/php82.yml">
    <img src="https://github.com/WebFiori/err/workflows/Build%20PHP%208.1/badge.svg?branch=main">
  </a>
  <a href="https://sonarcloud.io/dashboard?id=WebFiori_err">
      <img src="https://sonarcloud.io/api/project_badges/measure?project=WebFiori_err&metric=alert_status" />
  </a>
  <a href="https://packagist.org/packages/webfiori/err">
    <img src="https://img.shields.io/packagist/dt/webfiori/err?color=light-green">
  </a>
</p>

## Supported PHP Versions
| Build Status |
|:-----------:|
|<a target="_blank" href="https://github.com/WebFiori/err/actions/workflows/php70.yml"><img src="https://github.com/WebFiori/err/workflows/Build%20PHP%207.0/badge.svg?branch=main"></a>|
|<a target="_blank" href="https://github.com/WebFiori/err/actions/workflows/php71.yml"><img src="https://github.com/WebFiori/err/workflows/Build%20PHP%207.1/badge.svg?branch=main"></a>|
|<a target="_blank" href="https://github.com/WebFiori/err/actions/workflows/php72.yml"><img src="https://github.com/WebFiori/err/workflows/Build%20PHP%207.2/badge.svg?branch=main"></a>|
|<a target="_blank" href="https://github.com/WebFiori/err/actions/workflows/php73.yml"><img src="https://github.com/WebFiori/err/workflows/Build%20PHP%207.3/badge.svg?branch=main"></a>|
|<a target="_blank" href="https://github.com/WebFiori/err/actions/workflows/php74.yml"><img src="https://github.com/WebFiori/err/workflows/Build%20PHP%207.4/badge.svg?branch=main"></a>|
|<a target="_blank" href="https://github.com/WebFiori/err/actions/workflows/php80.yml"><img src="https://github.com/WebFiori/err/workflows/Build%20PHP%208.0/badge.svg?branch=main"></a>|
|<a target="_blank" href="https://github.com/WebFiori/err/actions/workflows/php81.yml"><img src="https://github.com/WebFiori/err/workflows/Build%20PHP%208.1/badge.svg?branch=main"></a>|
|<a target="_blank" href="https://github.com/WebFiori/err/actions/workflows/php82.yml"><img src="https://github.com/WebFiori/err/workflows/Build%20PHP%208.2/badge.svg?branch=main"></a><br>(dev)|

## Installation
The library can be included in your project by including following entry in your `require` section of your `composer.json`: `"webfiori/err":"*"`.

## Features
* Conversion of all PHP errors to exceptions.
* Ability to create a custom exceptions handler.
* Provides OOP abstraction for the function `set_exception_handler()`

## Usage

The library has two main classes that the developer will work with. The first one is the class `Handler` and the second class is `AbstractExceptionHandler`. The first class is the core of the library. It is used to set custom exception handler. The second class is used to implement custom exceptions handler. Since the library will convert all PHP errors to exceptions, no need for the developer to have a custom errors handler.

### Implementing a Custom Exceptions Handler

First step in setting a custom exceptions handler is to implement one. Implementing a custom handler is strait forward procedure. Simply, the developer have to extends the class `AbstractExceptionHandler` and implement one abstract method of the class. The method `AbstractExceptionHandler::handle()` is used to handle the exception. The developer can have access to the properties of the thrown exception in order to handle it properly. The library comes with one default exceptions handler which can act as good example in how to implement a custom handler.

``` php
<?php
namespace webfiori\error;

class DefaultExceptionsHandler extends AbstractExceptionHandler {
    public function __construct() {
        parent::__construct();
    }
    /**
     * Handles the exception.
     */
    public function handle() {
        echo '<pre>';
        echo 'An exception was thrown at '.$this->getClass().' line '.$this->getLine().'.<br>';
        echo 'Exception message: '.$this->getMessage().'.<br>';
        echo 'Stack trace:<br>';
        $trace = $this->getTrace();
        
        if (count($trace) == 0) {
            echo '&lt;Empty&gt;';
        } else {
            $index = '0';

            foreach ($trace as $entry) {
                echo '#'.$index.' '.$entry.'<br>';
                $index++;
            }
        }
        echo '</pre>';
    }
}

```

### Setting a Custom Exceptions Handler

After implementing the handler, the developer must set it as exceptions handler. To do that, simply the developer have to use the method `Handler::setHandler()` in any place in his source code.

``` php
Handler::setHandler(new DefaultExceptionsHandler());
```


