# OpenSkos-API
Restful API for OpenSkos

## Development

### Docker environment

* `docker-composer up -d`
* copy the `data/docker/.env` file to the root folder, and adapt as necessary

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
* `docker-compose up --build`

## Editing ontology

After every change, you'll need to run `php bin/console ontology:generate`,
`php bin/console exception:errorlist` and `php bin/console swagger:generate`.

### Adding a namespace

- go to `config/ontology/`
- copy one of the existing yaml files and give it the right name (`&lt;namespace%gt;.yaml`)
- modify all name references inside your new file
- add all the fields you require

### Adding a field

- Add your field as named entry under `properties`.
- Configure you field's datatype
- If more properties are needed for your field, add them with their shortname as key

## Editing errors

After every change, you'll need to run `php bin/console exception:errorlist` to update error
definitions.

You can use the `/errors` endpoint to see what errors are configured. If you have access to the
files of the instance, you can look at `src/Exception/list.json` to see the error definitions.

### Adding a new error

Take a look at `src/OpenSkos/User/Controller/UserController.php` for examples

- Add `use App\Annotation\Error` to the top of your file
- Add `use App\Exception\ApiException` to the top of your file
- Add an `@Error` annotation describing your error in the docblock of your method
- Throw a new `ApiException(&lt;errorcode&gt;)` and let the ExceptionListener apply your config
