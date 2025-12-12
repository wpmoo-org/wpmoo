<?php

namespace WPMoo\App;

/**
 * Simple dependency injection container.
 *
 * @package WPMoo
 * @since 0.1.0
 * @link https://wpmoo.org   WPMoo – WordPress Micro Object-Oriented Framework.
 * @link https://github.com/wpmoo/wpmoo   GitHub Repository.
 * @license https://spdx.org/licenses/GPL-2.0-or-later.html   GPL-2.0-or-later
 */
class Container {
	/**
	 * Service bindings.
	 *
	 * @var array<string, mixed>
	 */
	private array $bindings = array();

	/**
	 * Singleton instances.
	 *
	 * @var array<string, mixed>
	 */
	private array $instances = array();

	/**
	 * Bind a service to the container.
	 *
	 * @param string                                                       $service_name Service identifier.
	 * @param (callable(Container):mixed)|array<string, mixed>|string|null $concrete Service resolver or class name.
	 * @return void
	 */
	public function bind( string $service_name, $concrete = null ): void {
		if ( is_null( $concrete ) ) {
			$concrete = $service_name;
		}

		$this->bindings[ $service_name ] = $concrete;
		unset( $this->instances[ $service_name ] );
	}

	/**
	 * Bind a singleton service to the container.
	 *
	 * @param string                                                       $service_name Service identifier.
	 * @param (callable(Container):mixed)|array<string, mixed>|string|null $concrete Service resolver or class name.
	 * @return void
	 */
	public function singleton( string $service_name, $concrete = null ): void {
		if ( is_null( $concrete ) ) {
			$concrete = $service_name;
		}

		$this->bindings[ $service_name ]  = $concrete;
		$this->instances[ $service_name ] = null;
	}

	/**
	 * Resolve a service from the container.
	 *
	 * @param string $service_name Service identifier.
	 * @return mixed   Resolved service instance.
	 * @throws \InvalidArgumentException When service cannot be resolved.
	 */
	public function resolve( string $service_name ) {
		if ( isset( $this->instances[ $service_name ] ) ) {
			return $this->instances[ $service_name ];
		}

		$concrete = $this->bindings[ $service_name ] ?? $service_name;

		if ( is_callable( $concrete ) ) {
			$instance = $concrete( $this );
		} elseif ( is_string( $concrete ) ) {
			$instance = $this->build( $concrete );
		} elseif ( is_array( $concrete ) ) {
			$instance = $this->build_from_array( $concrete );
		} else {
			throw new \InvalidArgumentException( 'Unable to resolve service' );
		}

		if ( isset( $this->instances[ $service_name ] ) ) {
			$this->instances[ $service_name ] = $instance;
		}

		return $instance;
	}

	/**
	 * Build a service instance from a class name.
	 *
	 * @param string $class_name Class name to instantiate.
	 * @return object  Created instance.
	 * @throws \InvalidArgumentException When class is not instantiable.
	 */
	private function build( string $class_name ) {
		$reflection = new \ReflectionClass( $class_name );

		if ( ! $reflection->isInstantiable() ) {
			throw new \InvalidArgumentException( 'Class is not instantiable' );
		}

		$constructor = $reflection->getConstructor();

		if ( ! $constructor ) {
			return new $class_name();
		}

		$parameters   = $constructor->getParameters();
		$dependencies = array();

		foreach ( $parameters as $parameter ) {
			$type = $parameter->getType();

			if ( $type && ! $type->isBuiltin() ) {
				$class_name     = method_exists( $type, 'getName' ) ? $type->getName() : (string) $type;
				$dependencies[] = $this->resolve( $class_name );
			} elseif ( $parameter->isDefaultValueAvailable() ) {
					$dependencies[] = $parameter->getDefaultValue();
			} else {
				throw new \InvalidArgumentException( 'Cannot resolve parameter' );
			}
		}

		return $reflection->newInstanceArgs( $dependencies );
	}

	/**
	 * Build a service instance from an array configuration.
	 *
	 * @param   array<string, mixed> $concrete Array with 'class' and optional 'method' keys.
	 * @return  object Created instance.
	 * @throws  \InvalidArgumentException When configuration is invalid.
	 */
	private function build_from_array( array $concrete ) {
		$class = $concrete['class'] ?? null;
		if ( null === $class ) {
			throw new \InvalidArgumentException( 'Array binding must have a class key' );
		}
		$method = $concrete['method'] ?? '__construct';

		if ( '__construct' === $method ) {
			return $this->build( $class );
		}

		$instance = $this->build( $class );
		return $instance->$method( $this );
	}

	/**
	 * Check if a service is bound in the container.
	 *
	 * @param string $service_name Service identifier.
	 * @return bool    True if service exists, false otherwise.
	 */
	public function has( string $service_name ): bool {
		return ( isset( $this->instances[ $service_name ] ) || isset( $this->bindings[ $service_name ] ) );
	}

	/**
	 * Register an existing instance in the container.
	 *
	 * @param string $service_name Service identifier.
	 * @param object $instance     Service instance.
	 * @return void
	 */
	public function instance( string $service_name, $instance ): void {
		$this->instances[ $service_name ] = $instance;
		unset( $this->bindings[ $service_name ] );
	}
}
