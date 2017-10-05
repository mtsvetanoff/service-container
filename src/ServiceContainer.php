<?php namespace MTsvetanoff\ServiceContainer;

# Dependencies
use ReflectionClass,
	Exception;

/**
 * A simple service container with autowiring capabilities
 * --------------------------------------------
 * @author    Martin Tsvetanov <m.tsvetanoff@gmail.com>
 * @package   MTsvetanoff\ServiceContainer\ServiceContainer
 */

class ServiceContainer {

	# Properties
	private $enableAutoWiring;
	private $services = [];

	/**
	 * Class constructor
	 *
	 * @param boolean $enableAutoWiring (Optional)
	 *
	 * @access public
	 */
	public function __construct($enableAutoWiring = true) {

		# Set properties
		$this->enableAutoWiring = $enableAutoWiring;
	}

	/**
	 * Finds an entry of the container by its identifier and returns it
	 *
	 * @param string $name
	 *
	 * @return mixed
	 *
	 * @throws Exception
	 *
	 * @access public
	 */
	public function get($name) {

		# Search for the implementations of interfaces and abstract classes
		$name = str_replace(['Interface', 'Abstract'], '', $name);

		# The class has already been initialized
		if ($this->has($name)) return $this->services[$name];

		# The class has not been initialized and autowiring is disabled
		else if (!$this->enableAutoWiring) throw new Exception('Service ' . $name . ' does not exist in the service container');

		# Throw an exception if the class does not exist
		if (!class_exists($name) AND !interface_exists($name)) throw new Exception('Class ' . $name . ' does not exist');

		# Reflect the requested class
		$reflector = new ReflectionClass($name);

		# If the reflector is not instantiable, it's probably an interface or an abstract class which does not have a defined implementation
		if (!$reflector->isInstantiable()) throw new Exception('Class ' . $name . ' is not instantiable');

		# Get class constructor
		$constructor = $reflector->getConstructor();
		
		# The class has a constructor
		if ($constructor) {
		
			# Get constructor parameters
			$params = $constructor->getParameters();
		}

		# The class does not have a constructor or the constructor does not have any parameters
		if (!$constructor OR count($params) === 0) {

			# Instantiate service
			$service = $reflector->newInstance();
			
			# Set service
			$this->set($name, $service);
			
			# Return service
			return $service;
		}

		# This is were we store the dependencies
		$dependencies = [];
		
		# Loop over the constructor parameters
		foreach ($params as $i => $param) {

			# Get the class which has been type hinted
			$class = $param->getClass();

			# The parameter has a type hint
			if ($class) {

				# Store dependency
				$dependencies[] = $this->get($class->getName());
			}
			
			# The parameter does not have a type hint
			else throw new Exception('Argument ' . $i . ' in class ' . $name . ' cannot be autowired');
		}

		# Instantiate service
		$service = $reflector->newInstanceArgs($dependencies);
		
		# Set service
		$this->set($name, $service);
		
		# Return service
		return $service;
	}
	
	/**
	 * Returns true if the container can return an entry for the given identifier
	 *
	 * @param string $name
	 *
	 * @return boolean
	 *
	 * @access public
	 */
	public function has($name) {
		
		# Check if the service exists
		return array_key_exists($name, $this->services);
	}

	/**
	 * Adds an entry to the container
	 *
	 * @param string	$name
	 * @param mixed		$value
	 *
	 * @access public
	 */
	public function set($name, $value) {

		# Set service
		$this->services[$name] = $value;
	}

	/**
	 * Removes an entry from the container
	 *
	 * @param string $name
	 *
	 * @access public
	 */
	public function remove($name) {

		# Service exists
		if ($this->has($name)) {
			
			# Remove service
			unset($this->services[$name]);
		}
	}
}