# OpenSkos-API
Restful API for OpenSkos

## Development

### Installation:

* `composer install`
* `vendor/bin/grumphp git:init` -- grump will make sure that you have no mistakes before commit ;)

### Tests

Test are written using [PHPSpec](https://www.phpspec.net/en/stable/manual/introduction.html)
Run tests by: `vendor/bin/phpspec run`

### Static analyser

To have code strictly typed with nullability checks and etc. [Psalm](https://psalm.dev/) will help.
Run psalm by: `vendor/bin/psalm`

### Development procedure:
* Pull latest master
* Create feature/bug branch `feature/code-and-short-title`
* Commit all needed changes
* Write documentation if needed under `doc/feature-name.md`
* Make sure you're code is covered by tests at least critical parts
* Submit PR to github.
* Wait for Approvals from dev-team and get ready for changes during PR discussion
* ...?
* Profit!

## Docker
TODO
