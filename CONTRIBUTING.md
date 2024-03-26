# CONTRIBUTING

Contributions are welcome, and are accepted via pull requests.
Please review these guidelines before submitting any pull requests.

## Process

1. Fork the project
1. Create a new branch
1. Code, test, commit and push
1. Open a pull request detailing your changes. Make sure to follow the [template](.github/PULL_REQUEST_TEMPLATE.md)

## Guidelines

* Please follow the [PSR-2 Coding Style Guide](http://www.php-fig.org/psr/psr-2), enforced by [StyleCI](https://styleci.io).
* Send a coherent commit history, making sure each individual commit in your pull request is meaningful.
* You may need to [rebase](https://git-scm.com/book/en/v2/Git-Branching-Rebasing) to avoid merge conflicts.
* Please remember that we follow [SemVer](http://semver.org).

## Setup

Clone your fork, then install all dependencies:

    composer update

## Tests

Run code style checks:

    composer test:cs

Run all tests:

    composer test

Code analysis:

    composer test:types

Unit tests:

    composer test:unit

Run Larastan on a sample Laravel application:

    composer test:application
