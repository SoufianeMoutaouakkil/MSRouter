<?php

namespace Test;

use PHPUnit\Framework\TestCase;

use SMRouter\SMRouter;
use SMRouter\Exception\SMInvalidPathException;

class SMRouterTest extends TestCase
{

    private $configDir;
    private $config;

    public function __construct()
    {
        parent::__construct();

        $this->configDir = (\dirname(__FILE__) . "/testFiles/");
        $this->config = SMConfig::getInstance();

    }

    
//*********************************************
    public function testInstantiateError()
    {
        $this->expectException(\Error::class);
        $config = new SMConfig;
        unset($config);
    }

    
    public function testSetNotFoundFileConfig()
    {
        $this->expectException(SMInvalidPathException::class);
        $this->config->setConfigByFiles(
            [ "notFound" => $this->configDir . "notFound.json"
        ]);
    }
    
    public function testSetInvalidConfigNameFileConfig()
    {
        $this->expectException(SMInvalidConfigNameException::class);
        $this->config->setConfigByFiles([
            0 => $this->configDir . "PhpConfig.json"
        ]);
    }

    public function testSetInvalidConfigNameArrayConfig()
    {
        $this->expectException(SMInvalidConfigNameException::class);
        $this->config->setConfigByValues([
            0 => "Data"
        ]);
    }

    public function testSetInvalidContentJsonFileConfig()
    {
        $this->expectException(SMInvalidFileContentException::class);
        $this->config->setConfigByFiles(
            [ "invalidJson" => $this->configDir . "invalidContent.json"
        ]);
    }

    public function testSetInvalidContentPhpFileConfig()
    {
        $this->expectException(SMInvalidFileContentException::class);
        $this->config->setConfigByFiles(
            [ "invalidJson" => $this->configDir . "invalidContent.php"
        ]);
    }

    public function testSetInvalidExtFileConfig()
    {
        $this->expectException(SMInvalidFileExtException::class);
        $this->config->setConfigByFiles(
            [ "notAccepted" => $this->configDir . "Config.yml"
        ]);
    }

    public function testSetJsonFileConfig()
    {
        $this->config->setConfigByFiles(["json" => $this->configDir . "Config.json"]);

        $this->assertArrayHasKey("json", $_ENV);
        $this->assertArrayHasKey("key1", $_ENV["json"]);
        $this->assertEquals("key1Value", $_ENV["json"]["key1"]);
    }

    public function testSetPhpFileConfig()
    {
        // test the use of a php file. it will be added to the current config in the object
        $this->config->setConfigByFiles(["php" => $this->configDir . "Config.php"]);

        // make sure that the test testSetJsonFileConfig has effect on the global object of SMConfig
        $this->assertArrayHasKey("json", $_ENV);
        $this->assertArrayHasKey("php", $_ENV);
        $this->assertArrayHasKey("database", $_ENV["php"]);
        $this->assertArrayHasKey("mysql", $_ENV["php"]["database"]);
        $this->assertEquals("root", $_ENV["php"]["database"]["mysql"]["db_user"]);
    }
}
