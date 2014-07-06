<?php

use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Testwork\Hook\Scope\BeforeSuiteScope;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Behat context class.
 */
class FeatureContext implements SnippetAcceptingContext
{
    protected static $includePath;

    /**
     * @var string
     */
    protected $workingDir;
    /**
     * @var string
     */
    protected $binDir;
    /**
     * @var string
     */
    protected $phpBin;
    /**
     * @var Process
     */
    protected $process;

    /**
     * Initializes context.
     *
     * Every scenario gets its own context object.
     * You can also pass arbitrary arguments to the context constructor through behat.yml.
     */
    public function __construct()
    {
    }

    /**
     * Cleans test folders in the temporary directory.
     *
     * @BeforeSuite
     */
    public static function storeIncludePath()
    {
        self::$includePath = get_include_path();
    }

    /**
     * Cleans test folders in the temporary directory.
     *
     * @AfterScenario
     */
    public static function cleanTestFolders()
    {
        if (is_dir($dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'filler')) {
            self::clearDirectory($dir);
        }
    }

    /**
     * Prepares test folders in the temporary directory.
     *
     * @BeforeScenario
     */
    public function prepareTestFolders()
    {
        $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'filler' . DIRECTORY_SEPARATOR .
            md5(microtime() * rand(0, 10000));

        mkdir($dir . '/fixtures', 0777, true);

        $phpFinder = new PhpExecutableFinder();
        if (false === $php = $phpFinder->find()) {
            throw new \RuntimeException('Unable to find the PHP executable.');
        }
        $this->workingDir = $dir;
        $this->binDir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR;
        $this->phpBin = $php;
        $this->process = new Process(null);
    }

    /**
     * @Given the following propel schema:
     */
    public function theFollowingPropelSchema(PyStringNode $string)
    {
        $this->preparePropel();
        $this->aFileNamedWith('schema.xml', $string);

        $command = $this->binDir . 'propel-gen . main';
        $this->process->setWorkingDirectory($this->workingDir);
        $this->process->setCommandLine($command)->run();

        $this->process->setWorkingDirectory($this->workingDir);
        $command = $this->binDir . 'propel-gen . insert-sql';
        $this->process->setCommandLine($command)->run();

        $classPath = $this->workingDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'classes';
        set_include_path($classPath . PATH_SEPARATOR . self::$includePath);

        $config = $this->workingDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'conf' . DIRECTORY_SEPARATOR . 'filler-conf.php';
        Propel::init($config);
    }

    /**
     * Creates a file with specified name and context in current workdir.
     *
     * @Given /^(?:there is )?a file named "([^"]*)" with:$/
     *
     * @param   string       $filename name of the file (relative path)
     * @param   PyStringNode $content  PyString string instance
     */
    public function aFileNamedWith($filename, $content)
    {
        if ($content instanceof PyStringNode) {
            $content = strtr((string) $content, array("'''" => '"""'));
        }
        $this->createFile($this->workingDir . DIRECTORY_SEPARATOR . $filename, $content);
    }

    /**
     * Runs behat command with provided parameters
     *
     * @When /^I run "filler(?: ((?:\"|[^"])*))?"$/
     *
     * @param   string $argumentsString
     */
    public function iRunFiller($argumentsString = '')
    {
        $argumentsString = str_replace('{dir}', $this->workingDir, strtr($argumentsString, array('\'' => '"')));

        $this->process->setWorkingDirectory($this->workingDir);
        $command = sprintf(
            '%s %s %s',
            $this->phpBin,
            escapeshellarg($this->binDir . 'filler'),
            $argumentsString
        );
        $this->process->setCommandLine($command);
        $this->process->start();
        $this->process->wait();
    }

    /**
     * @Then the :name table should contain:
     */
    public function tableShouldContain($name, TableNode $table)
    {
        $queryClass = sprintf('%sQuery', ucfirst($name));
        $query = new $queryClass;
        /** @var \PropelCollection $collection */
        $collection = $query->find();

        foreach ($table->getHash() as $index => $rowHash) {
            $object = $collection->get($index);
            foreach ($rowHash as $key => $value) {
                $getter = 'get' . ucfirst($key);
                $objectValue = call_user_func([$object, $getter]);
                \PHPUnit_Framework_Assert::assertEquals($value, $objectValue);
            }
        }
    }

    /**
     * Recursively remove a directory and all it's contents
     *
     * @param $path
     */
    private static function clearDirectory($path)
    {
        $files = scandir($path);
        array_shift($files);
        array_shift($files);

        foreach ($files as $file) {
            $file = $path . DIRECTORY_SEPARATOR . $file;
            if (is_dir($file)) {
                self::clearDirectory($file);
            } else {
                unlink($file);
            }
        }

        rmdir($path);
    }

    /**
     * Create a file with the given contents
     *
     * @param $filename
     * @param $content
     */
    private function createFile($filename, $content)
    {
        $path = dirname($filename);
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        file_put_contents($filename, $content);
    }

    /**
     * Prepare working directory for a Propel connection
     */
    private function preparePropel()
    {
        $buildProps = <<<ENDPROPS
# Database driver
propel.database = sqlite
propel.database.url = sqlite:{$this->workingDir}/filler.db

propel.output.dir = {$this->workingDir}/build

# Project name
propel.project = filler
ENDPROPS;
        $this->aFileNamedWith('build.properties', $buildProps);

        $runtimeConf = <<<ENDRUNTIME
<config>
  <propel>
    <datasources default="filler">
      <datasource id="filler">
        <adapter>sqlite</adapter>
        <connection>
          <dsn>sqlite:{$this->workingDir}/filler.db</dsn>
          <user></user>
          <password></password>
        </connection>
      </datasource>
    </datasources>
  </propel>
</config>
ENDRUNTIME;
        $this->aFileNamedWith('runtime-conf.xml', $runtimeConf);
    }
}
