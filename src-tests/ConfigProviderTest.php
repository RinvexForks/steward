<?php

namespace Lmc\Steward;

use Configula\Config;

class ConfigProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var array */
    protected $environmentVariables = [];

    protected function setUp()
    {
        $this->environmentVariables = ConfigHelper::getDummyConfig();

        ConfigHelper::unsetConfigInstance();
    }

    public function testShouldRetrieveConfigurationValuesFromEnvironmentAndUseCamelCaseKeysForThem()
    {
        ConfigHelper::setEnvironmentVariables($this->environmentVariables);
        $config = ConfigProvider::getInstance()->getConfig();

        // Property access
        $this->assertEquals('http://server.tld:4444', $config->serverUrl);
        // getItem() access
        $this->assertEquals('http://server.tld:4444', $config->getItem('serverUrl'));
        // all items retrieval
        $this->assertInternalType('array', $config->getItems());
    }

    public function testShouldMakeConfigOptionsAccessibleDirectlyThroughConfigProvider()
    {
        ConfigHelper::setEnvironmentVariables($this->environmentVariables);

        $this->assertEquals('http://server.tld:4444', ConfigProvider::getInstance()->serverUrl);
    }

    public function testShouldDetectEmptyConfigOption()
    {
        ConfigHelper::setEnvironmentVariables($this->environmentVariables);

        $nonEmptyValue = empty(ConfigProvider::getInstance()->serverUrl);
        $emptyValue = empty(ConfigProvider::getInstance()->capability);

        $this->assertFalse($nonEmptyValue);
        $this->assertTrue($emptyValue);
    }

    public function testShouldThrowExceptionWhenAccessingNotExistingConfigOptionThroughConfigProvider()
    {
        ConfigHelper::setEnvironmentVariables($this->environmentVariables);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Configuration option "notExisting" was not defined');

        ConfigProvider::getInstance()->notExisting;
    }

    public function testShouldOnlyHoldOneInstanceOfConfigObject()
    {
        ConfigHelper::setEnvironmentVariables($this->environmentVariables);
        $provider = ConfigProvider::getInstance();
        $firstInstance = $provider->getConfig();
        $this->assertInstanceOf(Config::class, $firstInstance);

        $secondInstance = ConfigProvider::getInstance()->getConfig();
        $this->assertSame($firstInstance, $secondInstance);
    }

    public function testShouldFailIfRequiredOptionIsNotDefined()
    {
        ConfigHelper::setEnvironmentVariables($this->environmentVariables);
        putenv('SERVER_URL'); // unset value

        $provider = ConfigProvider::getInstance();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('SERVER_URL environment variable must be defined');

        $provider->getConfig();
    }

    public function testShouldAllowToAddCustomConfigurationOptions()
    {
        $provider = ConfigProvider::getInstance();

        $provider->setCustomConfigurationOptions(['CUSTOM_OPTION']);

        // Set environment values for custom option
        $this->environmentVariables['CUSTOM_OPTION'] = 'new';
        ConfigHelper::setEnvironmentVariables($this->environmentVariables);

        $config = $provider->getConfig();

        $this->assertEquals('new', $config->customOption);
    }

    public function testShouldFailIfSettingCustomConfigurationOptionsAfterFirstInstantiation()
    {
        ConfigHelper::setEnvironmentVariables($this->environmentVariables);
        $provider = ConfigProvider::getInstance();
        // Create Config instance
        $provider->getConfig();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Custom configuration options can be set only before the Config object was instantiated'
        );

        // This should fail, as the Config instance was already created
        $provider->setCustomConfigurationOptions(['CUSTOM_OPTION']);
    }
}
