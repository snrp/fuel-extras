<?php

/**
 * @author		George Hincu
 * @license		MIT License
 */

namespace Ex;

class Sort {


 	/**
 	 * @var	string	The column that will be sorted by, this is computed and searched for in this order:
 	 * Input::get, static::$_default_column, and lastly, the first array element of static::$sortable_columns
 	 */
	protected static $_column = null; // Sort::column();

	/**
 	 * @var	string	same as $column
 	 */
	protected static $_direction = null; // Sort::direction();

	/**
 	 * @var	array	List of columns that are sortable. - Also, the input::get(column) is validated against $sortable_columns
 	 */
	public static $sortable_columns = null; // Sort::$sortable_columns = array('title', 'description')

	public static $labels = null;//used by create_links()

	/**
	 * @var	array	Optional. Prefered initial sort direction for columns.

	public static $column_initial_direction = null;	// Sort::$column_initial_direction = array('publish_date' => 'desc')
	 */

 	/**
 	 * @var	string	The get var name
 	 */
	public static $param_name_column = 'sort_by';

 	/**
 	 * @var	string	The get var name
 	 */
	public static $param_name_direction = 'direction';


	/**
	 * @var	array	Optional. The default_sort column and direction to sort by, if this is null, the first element of $sortable_columns is used
	 * 
	 */
	public static $default = null; // array('col', 'dir') 
	protected static $_default_column = null;
	protected static $_default_direction = null;


 	/**
 	 * @var	string	The uri route. This will be passed to UriEx::generate, together with static::$get_variables by static::create_link()
 	 */
	public static $uri = null;


 	/**
 	 * @var	array	Optional. You can set other get parameters for the sort link in here.
	 * This will be passed to UriEx::generate, together with static::$uri by static::create_link()
 	 */
	public static $get_variables = array();


 	/**
 	 * @var	bool	Add some text to the currently sorted by link. Eg: Title (^)
 	 */
	public static $direction_arrow = true;

 	/**
 	 * @var	string	@see $direction_arrow
 	 */
	public static $direction_arrow_asc = '&nbsp;<span style="font-size:60%">&#9650</span>';

	/**
 	 * @var	string	@see $direction_arrow
 	 */
	public static $direction_arrow_desc = '&nbsp;<span style="font-size:60%">&#9660</span>';


 	/**
 	 * @var	array	Add attributes to the links. Set this to false or null to disable it, or disable each key individually
 	 */
	protected static $attributes_base = array();
	protected static $attributes_not_sorted = array();
	protected static $attributes_sorted_asc = array('class' => 'sorted asc');
	protected static $attributes_sorted_desc = array('class' => 'sorted desc');

	protected static $_initialized = false;


	// --------------------------------------------------------------------

	/**
	 * Set Config
	 *
	 * Sets the configuration for pagination
	 *
	 * @access public
	 * @param array   $config The configuration array
	 * @return void
	 */
	public static function set_config(array $config)
	{
		foreach ($config as $key => $value)
		{
			static::${$key} = $value;
		}
		static::$_initialized = false;
	}
	
	/**
	 *  static::$sortable_columns must not be empty
	 *	default_sort column exists in $sortable_columns
	 *	default_sort direction is asc / desc
	 */
	protected static function _verify_config()
	{ 
		if (empty(static::$sortable_columns)) {
			\Error::notice('Sort::$sortable_columns cannot be empty');
		}
	
		//default_sort column exists in $sortable_columns
		if ( ! empty(static::$_default_column)
		and ! in_array(static::$_default_column, static::$sortable_columns, true))
		{
			\Error::notice('The column_name "'.static::$_default_column.'" configured in $_default_column was not found among the column names listed in of $sortable_columns');
			static::$_default_column = null;
		}
	
		//default_sort direction is asc / desc
		if (is_array(static::$_default_direction) and ! in_array( strtolower(static::$_default_direction), array('asc', 'desc'), true))
		{
			\Error::notice('$_default_direction is set incorectly: "'.static::$_default_direction.'"');
			static::$_default_direction = null;
		}
	}

	/**
	 * verifies config, 
	 * processes the uri to get current sort column and dir
	 * verifies if the uri values are valid:
	 *		the sort column is found in static::$sortable_columns
	 *		the dir is asc / desc
	 *
	 */
	protected static function initialize()
	{	

		static::$_default_column = is_array(static::$default) && isset(static::$default[0]) ? static::$default[0] : null;
		static::$_default_direction =  is_array(static::$default) && isset(static::$default[1]) ? static::$default[1] : null;
	
		static::_verify_config();
	
		//default_sort col = either the first column or static::$_default_column if set
		$default_col = static::$_default_column ?: reset(static::$sortable_columns); // reset == first
		$default_dir = static::$_default_direction ? strtolower(static::$_default_direction) : 'asc';

		$input_col =  \Input::get(static::$param_name_column);
		$input_dir = strtolower( \Input::get(static::$param_name_direction));

		//check that the input values are valid
		if (in_array($input_col, static::$sortable_columns, true))
		{
			static::$_column = $input_col;
			static::$_direction = in_array($input_dir, array('asc', 'desc'), true) ? $input_dir : $default_dir;
		}
		else
		{
			static::$_column = $default_col;
			static::$_direction = $default_dir; //reset direction too if col is wrong
		}
		
		static::$_initialized = true;
	}

	/**
	 * get the name for the uri_param that holds the column name that will be sorted by
	 * @return string 
	 */
	public static function uri_col_name()
	{
		static::$_initialized or static::initialize();
		return static::$param_name_column;
	}
	
	/**
	 * get the name for the uri_param that holds the direction (asc / desc) that will be sorted by
	 * @return string 
	 */
	public static function uri_dir_name()
	{
		static::$_initialized or static::initialize();
		return static::$param_name_direction;
	}

	/**
	 * convenience function, returns this:
	 *		array(
	 *			\Ex\Sort::$param_name_column => \Ex\Sort::$_column,
	 *			\Ex\Sort::$param_name_direction => \Ex\Sort::$_direction)
	 *		)
	 * @return array
	 */
	public static function to_get_variables()
	{
		static::$_initialized or static::initialize();
		return array(
			static::$param_name_column => static::$_column,
			static::$param_name_direction => static::$_direction
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Creates a sort_link for the specified column, 
	 *
	 * if $link_text = null it will use ucfirst($column)
	 * 
	 * if the column is not sortable (not found among static::$sortable_columns)
	 *		it returns only text instead of link
	 * 
	 * if the $column is currently sorted by, 
	 *		it adds attributes from static::$attributes_sorted_asc/desc to the link
	 *		it ads an up arrow / down arrow character to the text of the link (static::$direction_arrow_... )
	 * else 
	 *		it adds static::$attributes_not_sorted;
	 * 
	 * it merges the above mentioned attr with static::$attributes_base
	 * 
	 * @uses static::create_url($column) to create the url for the link
	 * 
	 * 
	 * @access public
	 * @param string	$column name for which the limk is to be created
	 * @param string|null	$link_text if this is null, ucfirst($column) is used
	 * @return mixed    The pagination links
	 */
	public static function create_link($column, $link_text = null)
	{
		static::$_initialized or static::initialize();
	
		$link_text = is_null($link_text) ? ucfirst($column) : $link_text;

		//allow to call Sort::create_link from a foreach that loops through all columns,
		//but permit only some columns to be sortable
		if ( ! in_array($column, static::$sortable_columns))
		{
			return $link_text;
		}

		$link_url = static::create_url($column);

		if ($column === static::$_column)
		{
			$attr = static::${'attributes_sorted_'.static::$_direction};

			//append some symbols to the currently sorted by link for debugging purposes
			$link_text .= static::$direction_arrow == true ? static::${'direction_arrow_'.static::$_direction} : '';
		}
		else
		{
			$attr = static::$attributes_not_sorted;
		}

		return \Html::anchor($link_url, $link_text, array_merge(static::$attributes_base , $attr));
	}

	/**
	 * create array of sort_links / simple_text (for cols that are not sortable) 
	 * for easy display with foreach
	 * 
	 * $all_columns is optional, if missing , static::$sortable_columns will be used
	 * 
	 * in case not all your displayed cols are sortable, give all the cols as param, 
	 *		and set all the sortable cols in config in $sortable_columns
	 *
	 * create all col links; if a col in the parameter isnt listed in the $sortable_columns, 
	 *		only name is returned, else, a sort link is returned
	 *
	 * @param array - all cols
	 * @return array
	*/

	public static function create_links(array $all_columns = array())
	{
		static::$_initialized or static::initialize();
	
		$links = array();
		$all_columns = $all_columns ?: static::$sortable_columns;
		foreach ($all_columns as $name)
		{
			// create link only if col is sortable, else use a capitalized text label
			if (in_array($name, static::$sortable_columns))
			{
				$label = isset(static::$labels[$name]) ? static::$labels[$name] : null;
				$links[$name] = static::create_link($name, $label);
			}
			else
			{
				$label = isset(static::$labels[$name]) ? static::$labels[$name] : ucfirst($name);
				$links[$name] = $label;
			}
		}

		return $links;
	}

	/**
	 * Generate url from static::$uri followed by $_GET variables for the sort col and dir
	 * for the currently sorted by col, the oposite direction of current is put in the link
	 * @param string $column
	 * @return string hthl link 
	 */
 	public static function create_url($column)
 	{
 		static::$_initialized or static::initialize();
 	
 		if ( ! in_array($column, static::$sortable_columns))
		{
			return false;
		}

 		$get_vars = is_array(static::$get_variables) ? static::$get_variables : array();
		$get_vars[static::$param_name_column] = $column;

		if ($column === static::$_column)
		{
			//put the oposite direction to current dir
			$get_vars[static::$param_name_direction] = 'asc' === static::$_direction ? 'desc' : 'asc';
		}
		else
		{
			$get_vars[static::$param_name_direction] = 'asc';
		}

		return \Ex\Uri::generate(static::$uri, array(), $get_vars);
 	}

	/**
	 *
	 * @return type 
	 */
 	public static function column()
 	{
 		static::$_initialized or static::initialize();
 		return static::$_column;
 	}

 	public static function direction()
 	{
 		static::$_initialized or static::initialize();
 		return static::$_direction;
 	}
}

/* EOF */