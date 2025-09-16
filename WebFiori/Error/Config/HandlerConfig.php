<?php

namespace WebFiori\Error\Config;

/**
 * Configuration class for the error handler system.
 * 
 * This class manages configuration options for the error handling system
 * without modifying global PHP settings. It provides a safe way to configure
 * error handling behavior while respecting application-level configuration.
 * 
 * Features:
 * - Environment-aware configuration
 * - No global PHP setting modifications
 * - Configurable error reporting levels
 * - Safe defaults for production environments
 * 
 * Usage Example:
 * ```php
 * $config = new HandlerConfig();
 * $config->setErrorReporting(E_ALL & ~E_NOTICE);
 * $config->setDisplayErrors(false);
 * 
 * Handler::setConfig($config);
 * ```
 *
 * @author Ibrahim
 */
class HandlerConfig {
    
    /**
     * @var int Error reporting level
     */
    private int $errorReporting;
    
    /**
     * @var bool Whether to display errors
     */
    private bool $displayErrors;
    
    /**
     * @var bool Whether to display startup errors
     */
    private bool $displayStartupErrors;
    
    /**
     * @var bool Whether to modify global PHP settings
     */
    private bool $modifyGlobalSettings;
    
    /**
     * @var bool Whether to respect existing PHP settings
     */
    private bool $respectExistingSettings;
    
    /**
     * @var array<string, mixed> Original PHP settings backup
     */
    private array $originalSettings = [];
    
    /**
     * Initialize configuration with safe defaults.
     */
    public function __construct() {
        $this->loadDefaults();
    }
    
    /**
     * Load default configuration based on environment detection.
     */
    private function loadDefaults(): void {
        $isProduction = $this->detectProductionEnvironment();
        
        if ($isProduction) {
            // Production defaults - safe and minimal
            $this->errorReporting = E_ERROR | E_WARNING | E_PARSE;
            $this->displayErrors = false;
            $this->displayStartupErrors = false;
            $this->modifyGlobalSettings = false;
            $this->respectExistingSettings = true;
        } else {
            // Development defaults - more verbose but still safe
            $this->errorReporting = E_ALL;
            $this->displayErrors = true;
            $this->displayStartupErrors = true;
            $this->modifyGlobalSettings = false; // Still don't modify by default
            $this->respectExistingSettings = true;
        }
    }
    
    /**
     * Detect if we're in a production environment.
     */
    private function detectProductionEnvironment(): bool {
        // Check environment variables
        $env = getenv('APP_ENV') ?: getenv('ENVIRONMENT');
        if ($env === 'production' || $env === 'prod') {
            return true;
        }
        
        // Check if display_errors is disabled (common in production)
        if (!ini_get('display_errors')) {
            return true;
        }
        
        // Check for production constants
        if (defined('PRODUCTION') && PRODUCTION === true) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Set error reporting level.
     * 
     * @param int $level Error reporting level (use E_* constants)
     * @return self
     */
    public function setErrorReporting(int $level): self {
        $this->errorReporting = $level;
        return $this;
    }
    
    /**
     * Get error reporting level.
     * 
     * @return int
     */
    public function getErrorReporting(): int {
        return $this->errorReporting;
    }
    
    /**
     * Set whether to display errors.
     * 
     * @param bool $display
     * @return self
     */
    public function setDisplayErrors(bool $display): self {
        $this->displayErrors = $display;
        return $this;
    }
    
    /**
     * Get whether to display errors.
     * 
     * @return bool
     */
    public function shouldDisplayErrors(): bool {
        return $this->displayErrors;
    }
    
    /**
     * Set whether to display startup errors.
     * 
     * @param bool $display
     * @return self
     */
    public function setDisplayStartupErrors(bool $display): self {
        $this->displayStartupErrors = $display;
        return $this;
    }
    
    /**
     * Get whether to display startup errors.
     * 
     * @return bool
     */
    public function shouldDisplayStartupErrors(): bool {
        return $this->displayStartupErrors;
    }
    
    /**
     * Set whether to modify global PHP settings.
     * 
     * WARNING: Setting this to true will modify global PHP configuration.
     * Only enable this if you understand the implications.
     * 
     * @param bool $modify
     * @return self
     */
    public function setModifyGlobalSettings(bool $modify): self {
        $this->modifyGlobalSettings = $modify;
        return $this;
    }
    
    /**
     * Get whether to modify global PHP settings.
     * 
     * @return bool
     */
    public function shouldModifyGlobalSettings(): bool {
        return $this->modifyGlobalSettings;
    }
    
    /**
     * Set whether to respect existing PHP settings.
     * 
     * @param bool $respect
     * @return self
     */
    public function setRespectExistingSettings(bool $respect): self {
        $this->respectExistingSettings = $respect;
        return $this;
    }
    
    /**
     * Get whether to respect existing PHP settings.
     * 
     * @return bool
     */
    public function shouldRespectExistingSettings(): bool {
        return $this->respectExistingSettings;
    }
    
    /**
     * Apply configuration settings.
     * 
     * This method applies the configuration while respecting the
     * modifyGlobalSettings and respectExistingSettings flags.
     */
    public function apply(): void {
        if (!$this->modifyGlobalSettings) {
            // Don't modify global settings, just store for internal use
            return;
        }
        
        // Backup original settings if we're going to modify them
        $this->backupOriginalSettings();
        
        // Apply settings based on configuration
        if (!$this->respectExistingSettings || ini_get('error_reporting') === false) {
            error_reporting($this->errorReporting);
        }
        
        if (!$this->respectExistingSettings || !ini_get('display_errors')) {
            ini_set('display_errors', $this->displayErrors ? '1' : '0');
        }
        
        if (!$this->respectExistingSettings || ini_get('display_startup_errors') === false) {
            ini_set('display_startup_errors', $this->displayStartupErrors ? '1' : '0');
        }
    }
    
    /**
     * Restore original PHP settings.
     */
    public function restore(): void {
        if (!$this->modifyGlobalSettings || empty($this->originalSettings)) {
            return;
        }
        
        foreach ($this->originalSettings as $setting => $value) {
            if ($setting === 'error_reporting') {
                error_reporting($value);
            } else {
                ini_set($setting, $value);
            }
        }
        
        $this->originalSettings = [];
    }
    
    /**
     * Backup original PHP settings.
     */
    private function backupOriginalSettings(): void {
        if (!empty($this->originalSettings)) {
            return; // Already backed up
        }
        
        $this->originalSettings = [
            'error_reporting' => error_reporting(),
            'display_errors' => ini_get('display_errors'),
            'display_startup_errors' => ini_get('display_startup_errors')
        ];
    }
    
    /**
     * Get current effective error reporting level.
     * 
     * This returns the actual error reporting level that should be used,
     * considering both configuration and existing PHP settings.
     * 
     * @return int
     */
    public function getEffectiveErrorReporting(): int {
        if ($this->respectExistingSettings) {
            $current = error_reporting();
            return $current !== false ? $current : $this->errorReporting;
        }
        
        return $this->errorReporting;
    }
    
    /**
     * Get current effective display errors setting.
     * 
     * @return bool
     */
    public function getEffectiveDisplayErrors(): bool {
        if ($this->respectExistingSettings) {
            $current = ini_get('display_errors');
            return $current !== false ? (bool)$current : $this->displayErrors;
        }
        
        return $this->displayErrors;
    }
    
    /**
     * Create a production-safe configuration.
     * 
     * @return self
     */
    public static function createProductionConfig(): self {
        $config = new self();
        $config->setErrorReporting(E_ERROR | E_WARNING | E_PARSE)
               ->setDisplayErrors(false)
               ->setDisplayStartupErrors(false)
               ->setModifyGlobalSettings(false)
               ->setRespectExistingSettings(true);
        
        return $config;
    }
    
    /**
     * Create a development configuration.
     * 
     * @return self
     */
    public static function createDevelopmentConfig(): self {
        $config = new self();
        $config->setErrorReporting(E_ALL)
               ->setDisplayErrors(true)
               ->setDisplayStartupErrors(true)
               ->setModifyGlobalSettings(false)
               ->setModifyGlobalSettings(true)
               ->setRespectExistingSettings(true);
        
        return $config;
    }
    
    /**
     * Create a legacy configuration that mimics old behavior.
     * 
     * WARNING: This will modify global PHP settings.
     * 
     * @return self
     */
    public static function createLegacyConfig(): self {
        $config = new self();
        $config->setErrorReporting(E_ALL)
               ->setDisplayErrors(true)
               ->setDisplayStartupErrors(true)
               ->setModifyGlobalSettings(true)
               ->setRespectExistingSettings(false);
        
        return $config;
    }
}
