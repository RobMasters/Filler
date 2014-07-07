Filler
======

[![Build Status](https://secure.travis-ci.org/RobMasters/Filler.png?branch=master)](http://travis-ci.org/RobMasters/Filler)

Filler is designed to be a fixtures library that makes life as simple as possible for developers. Unlike other popular fixture libraries, Filler doesn't make you responsible for specifying the order that fixtures need to load in order for dependencies to be available - you simply declare what dependencies you need where you need them.

Filler can be used with any persistance layer, such as Propel1, Propel2, Doctrine2, or even your own home-grown persistance layer. The only assumption that is made is that your model classes have setter methods (e.g. setName, setEmail) to load data. At present only Propel1 is supported out-of-the-box, but it is straight-forward to provide your own `Persistor` object to tell Filler how to persist your models.

Installation
------------

The easiest way to install Filler is by using [Composer](http://getcomposer.org):

```bash
$> curl -s https://getcomposer.org/installer | php
$> php composer.phar require robmasters/filler='dev-master'
```

### Configuring

Add a `filler.yml` file to the root of your project, or alternatively into a `config` directory:

```
filler:
    fixtures_path: fixtures
    connection:    propel                       # Value can be any of the top-level keys below

propel:
    config_file: build/conf/filler-conf.php     # Path to your Propel config file
    class_path:  build/classes                  # Path to your Propel classes directory

# TODO: add more connection types. Only Propel1 is supported for now
```

Defining Fixtures
-----------------

Add one or more fixture classes to your configured fixtures path. It is entirely up to you whether you prefer to use a
new class for each database table or subject area. The only requirement is that all fixture classes must implement the
`Filler\Fixture` interface.

For example, here are two fixture files that provide some data for a simple movie rental service:

**fixtures/UserFixtures.php**

```php
<?php

use Filler\Fixture;
use Filler\FixturesBuilder;

class UserFixtures implements Fixture
{
    /**
     * @param FixturesBuilder $builder
     * @return void
     */
    public function build(FixturesBuilder $builder)
    {
        $builder->build('User')
            ->add('mark_smith')
                ->name('Mark Smith')
                ->email('mark.smith@example.com')
                ->dateOfBirth('1987-04-25')
            ->add('helen_anderson')
                ->name('Helen Anderson')
                ->email('helen.anderson@example.com')
                ->dateOfBirth('1993-11-19')

            // Note that you don't need to provide a label for the fixture if nothing depends on it
            ->add()
                ->name('Tim Peters')
                ->email('tim.peters@example.com')
                ->dateOfBirth('1978-03-02')
            ->end()
        ;

        $this->buildUserRentals($builder);
    }

    /**
     * @param FixturesBuilder $builder
     * @return void
     */
    public function buildUserRentals(FixturesBuilder $builder)
    {
        $builder->depends(function($builder, User $markSmith, Movie $despicableMe) {
            $builder->build('UserRental')
                ->add()
                    ->userId($markSmith->getId())
                    ->movieId($despicableMe->getId())
                    ->date('2014-07-06 18:31:12')
                ->end()
            ;
        });

        $builder->depends(function($builder, User $helenAnderson, Movie $avatar, Movie $titanic) {
            $builder->build('UserRental')
                ->add()
                    ->userId($helenAnderson->getId())
                    ->movieId($avatar->getId())
                    ->date('2014-06-27 19:03:58')
                ->add()
                    ->userId($helenAnderson->getId())
                    ->movieId($titanic->getId())
                    ->date('2014-07-05 15:21:10')
                ->end()
            ;
        });
    }
}
```

**fixtures/MovieFixtures.php**

```php
<?php

use Filler\Fixture;
use Filler\FixturesBuilder;

class MovieFixtures implements Fixture
{
    /**
     * @param FixturesBuilder $builder
     * @return void
     */
    public function build(FixturesBuilder $builder)
    {
        $builder->build('Movie')
            ->add('avatar')
                ->title('Avatar')
                ->releaseDate('2009-12-18')
                ->runningLength(162)
            ->add('despicable_me')
                ->title('Despicable Me')
                ->releaseDate('2010-07-09')
                ->runningLength(95)
            ->add('titanic')
                ->title('Titanic')
                ->releaseDate('1997-12-19')
                ->runningLength(194)
            ->end()
        ;
    }
}
```

Key features:
* Filler's FixturesBuilder treats your model classes like 'Plain Old PHP Objects'. so it doesn't matter what persistance
layer your project uses. (Assuming a compatible `Persistor` instance is available).
* Unlike other fixture libraries Filler does not make you specify which order fixtures should be loaded, which can be
  very tricky to maintain in large projects. Instead, fixtures just specify any dependencies they need and these will be
  provided when they are available.


Loading Fixtures
----------------

### Loading via the console command

The simplest way to load fixtures is to use the console command. Assuming your project's "bin-dir" is "bin" (configured
via your `composer.json` file) then you simply need to execute the following from the root directory of your project:

```bash
$> php bin/filler fixtures:load
```

### Loading via PHP

Alternatively, you may wish to load fixtures from within your own system. Ideally this should be done using Dependency
Injection, but for simplicity's sake here is how you can construct the required objects to load fixtures:

```php
use Filler\Persistor\PropelPersistor;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Filler\DependencyManager;
use Filler\FixturesBuilder;
use Filler\FixturesLoader;

// persistor can be any class that implements Filler\Persistor\PersistorInterface
$persistor = new PropelPersistor();

// must be an instance of Symfony\Component\EventDispatcher\EventDispatcher, but it can be the same dispatcher
// used elsewhere in your project
$eventDispatcher = new EventDispatcher();

$dependencyManager = new DependencyManager($eventDispatcher);
$builder = new FixturesBuilder($persistor, $dependencyManager);

// the builder can either be passed to the loader's constructor, or it can be provided afterwards by
// passing it to the loader's setBuilder() method
$loader = new FixturesLoader($builder);

// Start loading fixtures
$loader->load();
```


To-do
-----

* Add more persistors (Propel2, Doctrine2)
* Better configuration, using Symfony Config component
