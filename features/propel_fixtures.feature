Feature: Propel fixtures
  In order to maintain a consistent datbase state
  As a tester
  I need to load fixtures using a propel connection

  Background:
    Given a file named "filler.yml" with:
    """
    filler:
        fixtures_path: fixtures
        connection: propel
    propel:
        config_file: build/conf/filler-conf.php
        class_path: build/classes
    """


  Scenario: Populate single table with data
    Given the following propel schema:
    """
    <database name="filler" defaultIdMethod="native">
      <table name="users" phpName="User" idMethod="native">
        <column name="id" phpName="Id" type="INTEGER" primaryKey="true" autoIncrement="true" required="true"/>
        <column name="name" phpName="Name" type="VARCHAR" size="255" required="true"/>
        <column name="email" phpName="Email" type="VARCHAR" size="255" required="true"/>
      </table>
    </database>
    """
    And a file named "fixtures/Users.php" with:
    """
    <?php

    use Filler\Fixture;
    use Filler\FixturesBuilder;

    class Users implements Fixture
    {
        public function build(FixturesBuilder $builder)
        {
            $builder
                ->build('User')
                    ->add()
                        ->name('barry')
                        ->email('barry@example.com')
                    ->add()
                        ->name('ralph')
                        ->email('ralph@example.com')
                    ->end()
                ->end()
            ;
        }
    }
    """
    When I run "filler fixtures:load"
    Then the user table should contain:
    | id | name  | email             |
    | 1  | barry | barry@example.com |
    | 2  | ralph | ralph@example.com |


  Scenario: Populate single table using parent dependency
    Given the following propel schema:
    """
    <database name="filler" defaultIdMethod="native">
      <table name="users_b" phpName="UserB" idMethod="native">
        <column name="id" phpName="Id" type="INTEGER" primaryKey="true" autoIncrement="true" required="true"/>
        <column name="name" phpName="Name" type="VARCHAR" size="255" required="true"/>
        <column name="email" phpName="Email" type="VARCHAR" size="255" required="true"/>
        <column name="supervisor_id" phpName="SupervisorId" type="INTEGER" required="false"/>
        <foreign-key foreignTable="users_b" name="fk_users_supervisor">
          <reference local="supervisor_id" foreign="id"/>
        </foreign-key>
      </table>
    </database>
    """
    And a file named "fixtures/Users.php" with:
    """
    <?php

    use Filler\Fixture;
    use Filler\FixturesBuilder;

    class Users implements Fixture
    {
        public function build(FixturesBuilder $builder)
        {
            $builder
                ->build('UserB')
                    ->add('supervisor')
                        ->name('barry')
                        ->email('barry@example.com')
                    ->add()
                        ->name('ralph')
                        ->email('ralph@example.com')
                    ->end()
            ;

            $builder->depends(function($b, UserB $supervisor) {
                $b
                    ->add()
                        ->name('sally')
                        ->email('sally@example.com')
                        ->supervisorId($supervisor->getId())
                    ->end()
                ;
            });
        }
    }
    """
    When I run "filler fixtures:load"
    Then the UserB table should contain:
    | id | name  | email             | supervisorId |
    | 1  | barry | barry@example.com |              |
    | 2  | ralph | ralph@example.com |              |
    | 3  | sally | sally@example.com | 1            |


  @wip
  Scenario: Populate multiple tables using multiple fixtures
    Given the following propel schema:
    """
    <database name="filler" defaultIdMethod="native">
      <table name="credits" phpName="Credit" idMethod="native">
        <column name="id" phpName="Id" type="INTEGER" primaryKey="true" autoIncrement="true" required="true"/>
        <column name="name" phpName="Name" type="VARCHAR" size="255" required="true"/>
        <column name="date_of_birth" phpName="DateOfBirth" type="VARCHAR" size="20" required="true"/>
      </table>

      <table name="genres" phpName="Genre" idMethod="native">
        <column name="id" phpName="Id" type="INTEGER" primaryKey="true" autoIncrement="true" required="true"/>
        <column name="name" phpName="Name" type="VARCHAR" size="50" required="true"/>
      </table>

      <table name="movies" phpName="Movie" idMethod="native">
        <column name="id" phpName="Id" type="INTEGER" primaryKey="true" autoIncrement="true" required="true"/>
        <column name="title" phpName="Title" type="VARCHAR" size="255" required="true"/>
        <column name="genre_id" phpName="GenreId" type="VARCHAR" size="255" required="true"/>
        <foreign-key foreignTable="genres" name="fk_movie_genres">
          <reference local="genre_id" foreign="id"/>
        </foreign-key>
      </table>

      <table name="movie_credits" phpName="MovieCredit" idMethod="native">
        <column name="id" phpName="Id" type="INTEGER" primaryKey="true" autoIncrement="true" required="true"/>
        <column name="movie_id" phpName="MovieId" type="INTEGER" required="true"/>
        <column name="credit_id" phpName="CreditId" type="INTEGER" required="true"/>
        <column name="role_id" phpName="RoleId" type="INTEGER" required="true"/>
        <column name="character_name" phpName="CharacterName" type="VARCHAR" size="255" required="false"/>
        <foreign-key foreignTable="movies" name="fk_movie_credit_movies">
          <reference local="movie_id" foreign="id"/>
        </foreign-key>
        <foreign-key foreignTable="credits" name="fk_movie_credit_credits">
          <reference local="credit_id" foreign="id"/>
        </foreign-key>
        <foreign-key foreignTable="roles" name="fk_movie_credit_roles">
          <reference local="role_id" foreign="id"/>
        </foreign-key>
      </table>

      <table name="roles" phpName="Role" idMethod="native">
        <column name="id" phpName="Id" type="INTEGER" primaryKey="true" autoIncrement="true" required="true"/>
        <column name="type" phpName="Type" type="VARCHAR" size="50" required="true"/>
      </table>
    </database>
    """
    And a file named "fixtures/CreditFixtures.php" with:
    """
    <?php

    use Filler\Fixture;
    use Filler\FixturesBuilder;

    class CreditFixtures implements Fixture
    {
        public function build(FixturesBuilder $builder)
        {
            $builder
                ->build('Credit')
                    ->add('kevin_bacon')
                        ->name('Kevin Bacon')
                        ->dateOfBirth('1958-07-08')
                    ->add('tom_hanks')
                        ->name('Tom Hanks')
                        ->dateOfBirth('1956-07-09')
                    ->add('brian_grazer')
                        ->name('Brian Grazer')
                        ->dateOfBirth('1951-07-12')
                    ->end()
                ->end()

                ->depends(function($builder, Credit $kevinBacon, Credit $tomHanks, Credit $brianGrazer, Role $actor, Role $producer, Movie $apollo13) {
                    $builder
                        ->build('MovieCredit')
                            ->add()
                                ->movieId($apollo13->getId())
                                ->creditId($kevinBacon->getId())
                                ->roleId($actor->getId())
                                ->characterName('Jack Swigert')
                            ->add()
                                ->movieId($apollo13->getId())
                                ->creditId($tomHanks->getId())
                                ->roleId($actor->getId())
                                ->characterName('Jim Lovell')
                            ->add()
                                ->movieId($apollo13->getId())
                                ->creditId($brianGrazer->getId())
                                ->roleId($producer->getId())
                            ->end()
                        ->end()
                    ;
                })
            ;
        }
    }
    """
    And a file named "fixtures/RoleFixtures.php" with:
    """
    <?php

    use Filler\Fixture;
    use Filler\FixturesBuilder;

    class RoleFixtures implements Fixture
    {
        public function build(FixturesBuilder $builder)
        {
            $builder
                ->build('Role')
                    ->add('actor')
                        ->type('Actor')
                    ->add('producer')
                        ->type('Producer')
                    ->end()
                ->end()
            ;
        }
    }
    """
    And a file named "fixtures/MovieFixtures.php" with:
    """
    <?php

    use Filler\Fixture;
    use Filler\FixturesBuilder;

    class MovieFixtures implements Fixture
    {
        public function build(FixturesBuilder $builder)
        {
            $builder
                ->build('Genre')
                    ->add('action')
                        ->name('Action')
                    ->end()
                ->end()

                ->depends(function($builder, Genre $action) {
                    $builder
                        ->build('Movie')
                            ->add('apollo13')
                                ->title('Apollo 13')
                                ->genreId($action->getId())
                            ->end()
                        ->end()
                    ;
                })
            ;
        }
    }
    """
    When I run "filler fixtures:load"
    Then the MovieCredit table should contain:
    | id | movieId | creditId | roleId | characterName |
    | 1  | 1       | 1        | 1      | Jack Swigert  |
    | 2  | 1       | 2        | 1      | Jim Lovell    |
    | 3  | 1       | 3        | 2      |               |