Awesome Support
==================

Awesome Support is the most advanced ticketing system for WordPress. This add-on creates a powerful API that can be used to interact with all of the components of the Awesome Support base plugin.

## Requirements

- WordPress 4.0+
- PHP 5.6+

## Installation

To get started right away:
```
git clone git@github.com:Awesome-Support/Rest-API.git awesome-support-api && cd awesome-support-api && composer install && npm install
```

### Not a developer?

If you're not a developer you're better off using the [production version available on www.awesomesupport.com](https://getawesomesupport.com/addons/awesome-support-rest-api/)

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
3. You should now be able to launch the default Grunt task with `grunt --force release`

## Contributing

Feel free to submit pull requests in github.  

### Change Log
-----------------------------------------------------------------------------------------
###### Version 1.0.2
- Tweak: Updated headers on main plugin file
- Tweak: Remove .zip file from version control
- Tweak: Remove invalid badges from readme.md file
- Tweak: Remove vendor folder from project - will be included dynamically during the build process
- Tweak: Clean up this readme.md file
- Fix: Plugin name

###### Version 1.0.1
- Internal Release

###### Version 1.0.0
- Initial Release
