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
            echo "before";
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
            echo "after";
        }
    }
    """
    When I run "filler fixtures:load"
    Then the user table should contain:
    | id | name  | email             |
    | 1  | barry | barry@example.com |
    | 2  | ralph | ralph@example.com |