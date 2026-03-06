Contributing guidelines [![Build Status](https://travis-ci.org/humanmade/comment-popularity.svg?branch=master)](https://travis-ci.org/humanmade/comment-popularity)
=======================

Coding Standards
----------------

Please follow the [WordPress Coding Standards](http://make.wordpress.org/core/handbook/coding-standards/)

Quality baseline (local + CI)
-----------------------------

From a clean checkout:

1. `composer install --no-interaction --prefer-dist --ignore-platform-reqs`
2. `composer lint`
3. `WP_VERSION=6.4 composer test:setup`
4. `composer test`

CI uses the same Composer scripts for consistency. The `--ignore-platform-reqs` flag is currently required because the locked `twig/twig` version predates modern PHP runtime constraints.

Contributions
-------------

Pull requests, reporting issues, feedback and ideas for new features and improvements are always welcome!

Releasing a new version
-----------------------

Obviously you'll need contributor access to the WordPress.org repository.

Install and run [the deployment script as per instructions](https://github.com/GaryJones/wordpress-plugin-svn-deploy)

Available Grunt tasks
---------------------

Linting: `grunt lint`
Minifying JS: `grunt minify`
Minify CSS: `cssmin`
