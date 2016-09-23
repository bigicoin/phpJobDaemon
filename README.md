# JobDaemon

A super lightweight PHP library to create a job daemon off of. Ideal for
queue consumers and usage in other types of producer/consumer architecture.
It is robust, free of memory leaks, and can run forever (provided that
the code extending from it are also free of memory leaks).

It has been used in several production environments ranging from
small to medium sized traffic, proven to work well to process millions
of queue entries a day.

## Features

- Completely open to process data of any kind. Simply extend the class
  and overload the parent process and child process methods and do
  whatever work you need.
- Flexible logging options.
- Tested with PHP 5.3 and above

## Installation

Grab a copy of the repository and move `JobDaemon.php` into your project
and place it wherever you want, and include/require the file.

### Using Composer

If you need to, [install composer](https://getcomposer.org/download/).

Create a `composer.json` file in your project root, or add JobDaemon
to it if you have an existing one.

```js
{
    "require": {
        "phpJobDaemon/phpJobDaemon": "dev-master"
    }
}
```

Install via composer by runnign this in your project root:

```sh
$ composer install
```
