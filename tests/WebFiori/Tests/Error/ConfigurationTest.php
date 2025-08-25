<?php

namespace WebFiori\Tests\Error;

use PHPUnit\Framework\TestCase;
use WebFiori\Error\Config\HandlerConfig;
use WebFiori\Error\Handler;

/**
 * Tests for configuration management and PHP settings handling.
 * 
 * @author Ibrahim
 */
class ConfigurationTest extends TestCase {
    
    /**
     * @var array<string, mixed> Original PHP settings backup
     */
    private array $originalSettings = [];
    
    protected function setUp(): void {
        // Backup original PHP settings
        $this->originalSettings = [
            'error_reporting' => error_reporting(),
            'display_errors' => ini_get('display_errors'),
            'display_startup_errors' => ini_get('display_startup_errors')
        ];
        
        Handler::reset();
    }
    
    protected function tearDown(): void {
        // Restore original PHP settings
        error_reporting($this->originalSettings['error_reporting']);
        ini_set('display_errors', $this->originalSettings['display_errors']);
        ini_set('display_startup_errors', $this->originalSettings['display_startup_errors']);
        
        Handler::shutdown();
    }
    
    /**
     * Test default configuration behavior.
     * 
     * @test
     */
    public function testDefaultConfiguration(): void {
        $config = new HandlerConfig();
        
        // Should not modify global settings by default
        $this->assertFalse($config->shouldModifyGlobalSettings());
        $this->assertTrue($config->shouldRespectExistingSettings());
        
        // Should have reasonable defaults
        $this->assertIsInt($config->getErrorReporting());
        $this->assertIsBool($config->shouldDisplayErrors());
        $this->assertIsBool($config->shouldDisplayStartupErrors());
    }
    
    /**
     * Test production configuration.
     * 
     * @test
     */
    public function testProductionConfiguration(): void {
        $config = HandlerConfig::createProductionConfig();
        
        // Production should be conservative
        $this->assertFalse($config->shouldDisplayErrors());
        $this->assertFalse($config->shouldDisplayStartupErrors());
        $this->assertFalse($config->shouldModifyGlobalSettings());
        $this->assertTrue($config->shouldRespectExistingSettings());
        
        // Should only report serious errors
        $expectedLevel = E_ERROR | E_WARNING | E_PARSE;
        $this->assertEquals($expectedLevel, $config->getErrorReporting());
    }
    
    /**
     * Test development configuration.
     * 
     * @test
     */
    public function testDevelopmentConfiguration(): void {
        $config = HandlerConfig::createDevelopmentConfig();
        
        // Development should be verbose but still safe
        $this->assertTrue($config->shouldDisplayErrors());
        $this->assertTrue($config->shouldDisplayStartupErrors());
        $this->assertFalse($config->shouldModifyGlobalSettings()); // Still safe by default
        $this->assertTrue($config->shouldRespectExistingSettings());
        
        // Should report all errors
        $this->assertEquals(E_ALL, $config->getErrorReporting());
    }
    
    /**
     * Test legacy configuration (for backward compatibility).
     * 
     * @test
     */
    public function testLegacyConfiguration(): void {
        $config = HandlerConfig::createLegacyConfig();
        
        // Legacy should mimic old behavior
        $this->assertTrue($config->shouldDisplayErrors());
        $this->assertTrue($config->shouldDisplayStartupErrors());
        $this->assertTrue($config->shouldModifyGlobalSettings()); // This is the key difference
        $this->assertFalse($config->shouldRespectExistingSettings());
        
        $this->assertEquals(E_ALL, $config->getErrorReporting());
    }
    
    /**
     * Test configuration application without global modification.
     * 
     * @test
     */
    public function testConfigurationApplicationSafe(): void {
        $originalErrorReporting = error_reporting();
        $originalDisplayErrors = ini_get('display_errors');
        
        $config = new HandlerConfig();
        $config->setErrorReporting(E_ERROR)
               ->setDisplayErrors(false)
               ->setModifyGlobalSettings(false); // Explicitly safe
        
        $config->apply();
        
        // PHP settings should remain unchanged
        $this->assertEquals($originalErrorReporting, error_reporting());
        $this->assertEquals($originalDisplayErrors, ini_get('display_errors'));
        
        // But configuration should remember the settings
        $this->assertEquals(E_ERROR, $config->getErrorReporting());
        $this->assertFalse($config->shouldDisplayErrors());
    }
    
    /**
     * Test configuration application with global modification.
     * 
     * @test
     */
    public function testConfigurationApplicationWithModification(): void {
        $config = new HandlerConfig();
        $config->setErrorReporting(E_ERROR)
               ->setDisplayErrors(false)
               ->setDisplayStartupErrors(false)
               ->setModifyGlobalSettings(true) // Allow modification
               ->setRespectExistingSettings(false); // Override existing
        
        $config->apply();
        
        // PHP settings should be modified
        $this->assertEquals(E_ERROR, error_reporting());
        $this->assertEquals('0', ini_get('display_errors'));
        $this->assertEquals('0', ini_get('display_startup_errors'));
        
        // Restore should work
        $config->restore();
        $this->assertEquals($this->originalSettings['error_reporting'], error_reporting());
        $this->assertEquals($this->originalSettings['display_errors'], ini_get('display_errors'));
    }
    
    /**
     * Test Handler configuration integration.
     * 
     * @test
     */
    public function testHandlerConfigurationIntegration(): void {
        $config = HandlerConfig::createProductionConfig();
        Handler::setConfig($config);
        
        $retrievedConfig = Handler::getConfig();
        $this->assertSame($config, $retrievedConfig);
        
        // Test that configuration is applied
        $this->assertFalse($retrievedConfig->shouldDisplayErrors());
        $this->assertEquals(E_ERROR | E_WARNING | E_PARSE, $retrievedConfig->getErrorReporting());
    }
    
    /**
     * Test configuration reset.
     * 
     * @test
     */
    public function testConfigurationReset(): void {
        // Set a custom configuration
        $customConfig = new HandlerConfig();
        $customConfig->setErrorReporting(E_ERROR)
                     ->setDisplayErrors(false);
        
        Handler::setConfig($customConfig);
        
        // Verify it's set
        $this->assertSame($customConfig, Handler::getConfig());
        
        // Reset configuration
        Handler::resetConfig();
        
        // Should have a new default configuration
        $newConfig = Handler::getConfig();
        $this->assertNotSame($customConfig, $newConfig);
        $this->assertInstanceOf(HandlerConfig::class, $newConfig);
    }
    
    /**
     * Test effective settings with respect for existing.
     * 
     * @test
     */
    public function testEffectiveSettingsWithRespect(): void {
        // Set some PHP settings
        error_reporting(E_WARNING);
        ini_set('display_errors', '1');
        
        $config = new HandlerConfig();
        $config->setErrorReporting(E_ERROR)
               ->setDisplayErrors(false)
               ->setRespectExistingSettings(true);
        
        // Effective settings should respect existing PHP settings
        $this->assertEquals(E_WARNING, $config->getEffectiveErrorReporting());
        $this->assertTrue($config->getEffectiveDisplayErrors());
    }
    
    /**
     * Test effective settings without respect for existing.
     * 
     * @test
     */
    public function testEffectiveSettingsWithoutRespect(): void {
        // Set some PHP settings
        error_reporting(E_WARNING);
        ini_set('display_errors', '1');
        
        $config = new HandlerConfig();
        $config->setErrorReporting(E_ERROR)
               ->setDisplayErrors(false)
               ->setRespectExistingSettings(false);
        
        // Effective settings should use configuration values
        $this->assertEquals(E_ERROR, $config->getEffectiveErrorReporting());
        $this->assertFalse($config->getEffectiveDisplayErrors());
    }
    
    /**
     * Test environment detection.
     * 
     * @test
     */
    public function testEnvironmentDetection(): void {
        // Test with environment variable
        putenv('APP_ENV=production');
        $config = new HandlerConfig();
        
        // Should detect production and use conservative defaults
        $this->assertFalse($config->shouldDisplayErrors());
        
        // Clean up
        putenv('APP_ENV');
    }
    
    /**
     * Test configuration chaining.
     * 
     * @test
     */
    public function testConfigurationChaining(): void {
        $config = new HandlerConfig();
        
        $result = $config->setErrorReporting(E_ALL)
                         ->setDisplayErrors(true)
                         ->setDisplayStartupErrors(false)
                         ->setModifyGlobalSettings(false);
        
        $this->assertSame($config, $result);
        $this->assertEquals(E_ALL, $config->getErrorReporting());
        $this->assertTrue($config->shouldDisplayErrors());
        $this->assertFalse($config->shouldDisplayStartupErrors());
        $this->assertFalse($config->shouldModifyGlobalSettings());
    }
    
    /**
     * Test that Handler doesn't modify global settings by default.
     * 
     * @test
     */
    public function testHandlerDoesNotModifyGlobalSettingsByDefault(): void {
        $originalErrorReporting = error_reporting();
        $originalDisplayErrors = ini_get('display_errors');
        $originalDisplayStartupErrors = ini_get('display_startup_errors');
        
        // Create a new handler instance
        Handler::reset();
        
        // Settings should remain unchanged
        $this->assertEquals($originalErrorReporting, error_reporting());
        $this->assertEquals($originalDisplayErrors, ini_get('display_errors'));
        $this->assertEquals($originalDisplayStartupErrors, ini_get('display_startup_errors'));
    }
    
    /**
     * Test Handler with legacy configuration.
     * 
     * @test
     */
    public function testHandlerWithLegacyConfiguration(): void {
        // Set legacy configuration that modifies global settings
        $legacyConfig = HandlerConfig::createLegacyConfig();
        Handler::setConfig($legacyConfig);
        
        // Force reinitialization
        Handler::reset();
        
        // Now global settings should be modified (like old behavior)
        $actualErrorReporting = error_reporting();
        $expectedErrorReporting = E_ALL;
        
        // Debug output if they don't match
        if ($actualErrorReporting !== $expectedErrorReporting) {
            $this->fail("Expected error reporting: $expectedErrorReporting, got: $actualErrorReporting");
        }
        
        $this->assertEquals(E_ALL, error_reporting());
        $this->assertEquals('1', ini_get('display_errors'));
        $this->assertEquals('1', ini_get('display_startup_errors'));
    }
    
    /**
     * Test configuration backup and restore.
     * 
     * @test
     */
    public function testConfigurationBackupAndRestore(): void {
        // Set initial PHP settings
        error_reporting(E_WARNING);
        ini_set('display_errors', '0');
        
        $config = new HandlerConfig();
        $config->setErrorReporting(E_ALL)
               ->setDisplayErrors(true)
               ->setModifyGlobalSettings(true)
               ->setRespectExistingSettings(false);
        
        // Apply configuration
        $config->apply();
        
        // Settings should be changed
        $this->assertEquals(E_ALL, error_reporting());
        $this->assertEquals('1', ini_get('display_errors'));
        
        // Restore should bring back original settings
        $config->restore();
        $this->assertEquals(E_WARNING, error_reporting());
        $this->assertEquals('0', ini_get('display_errors'));
    }
}
