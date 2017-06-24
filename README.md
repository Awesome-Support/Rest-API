Awesome Support
==================

[![Build Status](https://travis-ci.org/Awesome-Support/Rest-API.svg?branch=develop)](https://travis-ci.org/Awesome-Support/Rest-API) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Awesome-Support/Rest-API/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Awesome-Support/Rest-API/?branch=master) [![Code Coverage](https://scrutinizer-ci.com/g/Awesome-Support/Rest-API/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/Awesome-Support/Rest-API/?branch=master)

Awesome Support is the most advanced ticketing system for WordPress. This add-on creates a powerful API that can be used to interact with all of the components of the Awesome Support base plugin.

## Requirements

- WordPress 4.0+
- PHP 5.6+

Below are some info from the plugin's repository on [WordPress.org](https://wordpress.org/plugins/awesome-support-api/).

![WordPress plugin](https://img.shields.io/wordpress/plugin/v/Awesome-Support-API.svg?style=flat) ![Tested WordPress version](https://img.shields.io/wordpress/v/Awesome-Support-API.svg?style=flat) ![WordPress.org rating](https://img.shields.io/wordpress/plugin/r/Awesome-Support-API.svg?style=flat) [![Wordpress](https://img.shields.io/wordpress/plugin/dt/Awesome-Support-API.svg?style=flat)]()

## Installation

To get started right away:
```
git clone git@github.com:Awesome-Support/Rest-API.git awesome-support-api && cd awesome-support-api && composer install && npm install
```

### Not a developer?

If you're not a developer you're better off using the [production version available on WordPress.org](https://wordpress.org/plugins/awesome-support-api/).

### Dependencies

*If you're not familiar with Composer you should have a look at the [quick start guide](https://getcomposer.org/doc/00-intro.md).*

The development version works a little differently than the production version. The GitHub repo is "raw": the plugin dependencies aren't present.

#### Requirements

In order to work with the development branch you will need the following on your development environment:

- [Composer](https://getcomposer.org)
- [Node.js](http://nodejs.org/)

We use automated scripts to build the production version with all the required files, but if you wish to contribute or simply try the latest features on the development branch, you will need to install the dependencies manually.

Don't sweat it! It's no big deal. Dependencies are managed by Composer. Once you downloaded the `master` branch, there is only one thing you need to do: open the terminal at the plugin's location and type

```
composer install
```

This command will do a few things for you:

1. Install the plugin dependencies (via Composer)
2. Install Grunt & all Grunt modules (via `npm install`)
3. You should now be able to launch the default Grunt task with `grunt`

## Contributing

If you want to contribute to the plugin or just follow the development, all chats will be held on the Gitter channel.

[![Gitter](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/ThemeAvenue/Awesome-Support?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)
