<?php
/**
 * Dependency Injection Container
 *
 * Simple DI container for managing plugin dependencies and promoting loose coupling.
 * Implements lazy loading - services are only instantiated when requested.
 *
 * @package Keyless Auth
 * @since 3.3.0
 */

namespace Chrmrtns\KeylessAuth\Core;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Container class for dependency injection
 *
 * Provides a centralized registry for service objects, enabling:
 * - Lazy initialization (services created only when needed)
 * - Singleton pattern (one instance per service)
 * - Dependency management (clear service dependencies)
 * - Testability (easy to mock services in tests)
 *
 * @since 3.3.0
 */
class Container {

    /**
     * Service definitions (factories)
     *
     * @var array
     */
    private $services = array();

    /**
     * Service instances (singletons)
     *
     * @var array
     */
    private $instances = array();

    /**
     * Register a service factory
     *
     * @param string   $name    Service name (e.g., 'database', 'security_manager')
     * @param callable $factory Factory function that creates the service
     * @return void
     */
    public function register($name, $factory) {
        $this->services[$name] = $factory;
    }

    /**
     * Get a service instance
     *
     * Returns existing instance if available, otherwise creates new one.
     * Implements singleton pattern per service.
     *
     * @param string $name Service name
     * @return mixed Service instance
     * @throws \Exception If service not registered
     */
    public function get($name) {
        // Return existing instance if available
        if (isset($this->instances[$name])) {
            return $this->instances[$name];
        }

        // Check if service is registered
        if (!isset($this->services[$name])) {
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception message for developers, not end users
            throw new \Exception("Service '{$name}' not registered in container");
        }

        // Create new instance using factory
        $factory = $this->services[$name];
        $instance = $factory($this);

        // Store and return instance
        $this->instances[$name] = $instance;
        return $instance;
    }

    /**
     * Check if a service is registered
     *
     * @param string $name Service name
     * @return bool True if service is registered
     */
    public function has($name) {
        return isset($this->services[$name]);
    }

    /**
     * Set a service instance directly
     *
     * Useful for injecting pre-configured instances or mocks for testing.
     *
     * @param string $name     Service name
     * @param mixed  $instance Service instance
     * @return void
     */
    public function set($name, $instance) {
        $this->instances[$name] = $instance;
    }

    /**
     * Remove a service and its instance
     *
     * @param string $name Service name
     * @return void
     */
    public function remove($name) {
        unset($this->services[$name]);
        unset($this->instances[$name]);
    }

    /**
     * Clear all services and instances
     *
     * Useful for testing or plugin deactivation cleanup.
     *
     * @return void
     */
    public function clear() {
        $this->services = array();
        $this->instances = array();
    }

    /**
     * Get all registered service names
     *
     * @return array Array of service names
     */
    public function getServices() {
        return array_keys($this->services);
    }
}
