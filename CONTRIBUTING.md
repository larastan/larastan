CONTRIBUTING
============


Contributions are welcome, and are accepted via pull requests. Please review these guidelines before submitting any pull requests.


## Guidelines

* Please follow the [PSR-2 Coding Style Guide](http://www.php-fig.org/psr/psr-2/), enforced by [StyleCI](https://styleci.io/).
* Ensure that the current tests pass, and if you've added something new, add the tests where relevant.
* Send a coherent commit history, making sure each individual commit in your pull request is meaningful.
* You may need to [rebase](https://git-scm.com/book/en/v2/Git-Branching-Rebasing) to avoid merge conflicts.
* If you are changing the behavior, or the public api, you may need to update the docs.
* Please remember that we follow [SemVer](http://semver.org/).

We have [StyleCI](https://styleci.io/) setup to automatically fix any code style issues.

## Tests
Our current testsuite involves running `phpstan` on each individual file in `tests/Features` with our extension file loaded. The test will fail if phpstan fails on the given file. There is currently not a way for us to assert that larastan will fail for a given test suite, so it is difficult to test for expected failures in analysis.
