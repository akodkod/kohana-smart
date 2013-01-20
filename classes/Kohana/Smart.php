<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Acts as an object wrapper for HTML pages with embedded PHP, called "smarts".
 * Variables can be assigned with the smart object and referenced locally within
 * the smart.
 *
 * @package    Kohana
 * @category   Base
 * @author     Kohana Team
 * @copyright  (c) 2008-2011 Kohana Team
 * @license    http://kohanaframework.org/license
 */
class Kohana_Smart {

	// Array of global variables
	protected static $_global_data = array();

	/**
	 * Returns a new Smart object. If you do not define the "file" parameter,
	 * you must call [Smart::set_filename].
	 *
	 *     $smart = Smart::factory($file);
	 *
	 * @param   string  smart filename
	 * @param   array   array of values
	 * @return  Smart
	 */
	public static function factory($file = NULL, array $data = NULL)
	{
		return new Smart($file, $data);
	}

	/**
	 * Captures the output that is generated when a smart is included.
	 * The smart data will be extracted to make local variables. This method
	 * is static to prevent object scope resolution.
	 *
	 *     $output = Smart::capture($file, $data);
	 *
	 * @param   string  filename
	 * @param   array   variables
	 * @return  string
	 */
	protected static function capture($kohana_smart_filename, array $kohana_smart_data)
	{
		$render = new Smarty();
		$render->setCompileDir(APPPATH.'cache'.DIRECTORY_SEPARATOR.'templates_c');
		$render->assign($kohana_smart_data);

		// Capture the smart output
		ob_start();

		try
		{
			// Load the smart within the current scope
			$render->display($kohana_smart_filename);
		}
		catch (Exception $e)
		{
			// Delete the output buffer
			ob_end_clean();

			// Re-throw the exception
			throw $e;
		}

		// Get the captured output and close the buffer
		return ob_get_clean();
	}

	/**
	 * Sets a global variable, similar to [Smart::set], except that the
	 * variable will be accessible to all smarts.
	 *
	 *     Smart::set_global($name, $value);
	 *
	 * @param   string  variable name or an array of variables
	 * @param   mixed   value
	 * @return  void
	 */
	public static function set_global($key, $value = NULL)
	{
		if (is_array($key))
		{
			foreach ($key as $key2 => $value)
			{
				Smart::$_global_data[$key2] = $value;
			}
		}
		else
		{
			Smart::$_global_data[$key] = $value;
		}
	}

	/**
	 * Assigns a global variable by reference, similar to [Smart::bind], except
	 * that the variable will be accessible to all smarts.
	 *
	 *     Smart::bind_global($key, $value);
	 *
	 * @param   string  variable name
	 * @param   mixed   referenced variable
	 * @return  void
	 */
	public static function bind_global($key, & $value)
	{
		Smart::$_global_data[$key] =& $value;
	}

	// Smart filename
	protected $_file;

	// Array of local variables
	protected $_data = array();

	/**
	 * Sets the initial smart filename and local data. Smarts should almost
	 * always only be created using [Smart::factory].
	 *
	 *     $smart = new Smart($file);
	 *
	 * @param   string  smart filename
	 * @param   array   array of values
	 * @return  void
	 * @uses    Smart::set_filename
	 */
	public function __construct($file = NULL, array $data = NULL)
	{
		if ($file !== NULL)
		{
			$this->set_filename($file);
		}

		if ($data !== NULL)
		{
			// Add the values to the current data
			$this->_data = $data + $this->_data;
		}
	}

	/**
	 * Magic method, searches for the given variable and returns its value.
	 * Local variables will be returned before global variables.
	 *
	 *     $value = $smart->foo;
	 *
	 * [!!] If the variable has not yet been set, an exception will be thrown.
	 *
	 * @param   string  variable name
	 * @return  mixed
	 * @throws  Kohana_Exception
	 */
	public function & __get($key)
	{
		if (array_key_exists($key, $this->_data))
		{
			return $this->_data[$key];
		}
		elseif (array_key_exists($key, Smart::$_global_data))
		{
			return Smart::$_global_data[$key];
		}
		else
		{
			throw new Kohana_Exception('Smart variable is not set: :var',
				array(':var' => $key));
		}
	}

	/**
	 * Magic method, calls [Smart::set] with the same parameters.
	 *
	 *     $smart->foo = 'something';
	 *
	 * @param   string  variable name
	 * @param   mixed   value
	 * @return  void
	 */
	public function __set($key, $value)
	{
		$this->set($key, $value);
	}

	/**
	 * Magic method, determines if a variable is set.
	 *
	 *     isset($smart->foo);
	 *
	 * [!!] `NULL` variables are not considered to be set by [isset](http://php.net/isset).
	 *
	 * @param   string  variable name
	 * @return  boolean
	 */
	public function __isset($key)
	{
		return (isset($this->_data[$key]) OR isset(Smart::$_global_data[$key]));
	}

	/**
	 * Magic method, unsets a given variable.
	 *
	 *     unset($smart->foo);
	 *
	 * @param   string  variable name
	 * @return  void
	 */
	public function __unset($key)
	{
		unset($this->_data[$key], Smart::$_global_data[$key]);
	}

	/**
	 * Magic method, returns the output of [Smart::render].
	 *
	 * @return  string
	 * @uses    Smart::render
	 */
	public function __toString()
	{
		try
		{
			return $this->render();
		}
		catch (Exception $e)
		{
			// Display the exception message
			Kohana_Exception::handler($e);

			return '';
		}
	}

	/**
	 * Sets the smart filename.
	 *
	 *     $smart->set_filename($file);
	 *
	 * @param   string  smart filename
	 * @return  Smart
	 * @throws  Smart_Exception
	 */
	public function set_filename($file)
	{
		if (($path = Kohana::find_file(Kohana::$config->load('smart.folder'), $file, 'tpl')) === FALSE)
		{
			throw new Smart_Exception('The requested smart :file could not be found', array(
				':file' => $file,
			));
		}

		// Store the file path locally
		$this->_file = $path;

		return $this;
	}

	/**
	 * Assigns a variable by name. Assigned values will be available as a
	 * variable within the smart file:
	 *
	 *     // This value can be accessed as $foo within the smart
	 *     $smart->set('foo', 'my value');
	 *
	 * You can also use an array to set several values at once:
	 *
	 *     // Create the values $food and $beverage in the smart
	 *     $smart->set(array('food' => 'bread', 'beverage' => 'water'));
	 *
	 * @param   string   variable name or an array of variables
	 * @param   mixed    value
	 * @return  $this
	 */
	public function set($key, $value = NULL)
	{
		if (is_array($key))
		{
			foreach ($key as $name => $value)
			{
				$this->_data[$name] = $value;
			}
		}
		else
		{
			$this->_data[$key] = $value;
		}

		return $this;
	}

	/**
	 * Assigns a value by reference. The benefit of binding is that values can
	 * be altered without re-setting them. It is also possible to bind variables
	 * before they have values. Assigned values will be available as a
	 * variable within the smart file:
	 *
	 *     // This reference can be accessed as $ref within the smart
	 *     $smart->bind('ref', $bar);
	 *
	 * @param   string   variable name
	 * @param   mixed    referenced variable
	 * @return  $this
	 */
	public function bind($key, & $value)
	{
		$this->_data[$key] =& $value;

		return $this;
	}

	/**
	 * Renders the smart object to a string. Global and local data are merged
	 * and extracted to create local variables within the smart file.
	 *
	 *     $output = $smart->render();
	 *
	 * [!!] Global variables with the same key name as local variables will be
	 * overwritten by the local variable.
	 *
	 * @param    string  smart filename
	 * @return   string
	 * @throws   Smart_Exception
	 * @uses     Smart::capture
	 */
	public function render($file = NULL)
	{
		if ($file !== NULL)
		{
			$this->set_filename($file);
		}

		if (empty($this->_file))
		{
			throw new Smart_Exception('You must set the file to use within your smart before rendering');
		}

		// Combine local and global data and capture the output
		return Smart::capture($this->_file, $this->_data);
	}

} // End Kohana_Smart
