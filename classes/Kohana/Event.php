<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * Process queuing/execution class. Allows an unlimited number of callbacks
 * to be added to 'events'. Events can be run multiple times, and can also
 * process event-specific data.
 *
 * $Id: Event.php 4390 2009-06-04 03:05:36Z zombor $
 *
 * @package    Core
 * @author     Kohana Team
 * @copyright  (c) 2007 Kohana Team
 * @license    http://kohanaphp.com/license.html
 * @link       http://docs.kohanaphp.com/general/events
 */
class Kohana_Event {

	// Event callbacks
	protected static $_events = array();

	// Cache of events that have been run
	protected static $_has_run = array();

	// Stack of data for each event
	protected static $_stack = array();

	// Data that can be processed during events
	public static $data;

	/**
	 * Add a callback to an event queue.
	 *
	 * @param   string   event name
	 * @param   array    http://php.net/callback
	 * @return  boolean
	 */
	public static function add($name, $callback)
	{
		if ( ! isset(Event::$_events[$name]))
		{
			// Create an empty event if it is not yet defined
			Event::$_events[$name] = array();
		}
		elseif (in_array($callback, Event::$_events[$name], TRUE))
		{
			// The event already exists
			return FALSE;
		}

		// Add the event
		Event::$_events[$name][] = $callback;

		return TRUE;
	}

	/**
	 * Add a callback to an event queue, before a given event.
	 *
	 * @param   string   event name
	 * @param   array    existing event callback
	 * @param   array    event callback
	 * @return  boolean
	 */
	public static function add_before($name, $existing, $callback)
	{
		if (empty(Event::$_events[$name]) OR ($key = array_search($existing, Event::$_events[$name])) === FALSE)
		{
			// Just add the event if there are no events
			return Event::add($name, $callback);
		}
		else
		{
			// Insert the event immediately before the existing event
			return Event::insert_event($name, $key, $callback);
		}
	}

	/**
	 * Add a callback to an event queue, after a given event.
	 *
	 * @param   string   event name
	 * @param   array    existing event callback
	 * @param   array    event callback
	 * @return  boolean
	 */
	public static function add_after($name, $existing, $callback)
	{
		if (empty(Event::$_events[$name]) OR ($key = array_search($existing, Event::$_events[$name])) === FALSE)
		{
			// Just add the event if there are no events
			return Event::add($name, $callback);
		}
		else
		{
			// Insert the event immediately after the existing event
			return Event::insert_event($name, $key + 1, $callback);
		}
	}

	/**
	 * Inserts a new event at a specfic key location.
	 *
	 * @param   string   event name
	 * @param   integer  key to insert new event at
	 * @param   array    event callback
	 * @return  void
	 */
	protected static function insert_event($name, $key, $callback)
	{
		if (in_array($callback, Event::$_events[$name], TRUE))
			return FALSE;

		// Add the new event at the given key location
		Event::$_events[$name] = array_merge
		(
			// Events before the key
			array_slice(Event::$_events[$name], 0, $key),
			// New event callback
			array($callback),
			// Events after the key
			array_slice(Event::$_events[$name], $key)
		);

		return TRUE;
	}

	/**
	 * Replaces an event with another event.
	 *
	 * @param   string   event name
	 * @param   array    event to replace
	 * @param   array    new callback
	 * @return  boolean
	 */
	public static function replace($name, $existing, $callback)
	{
		if (empty(Event::$_events[$name]) OR ($key = array_search($existing, Event::$_events[$name], TRUE)) === FALSE)
			return FALSE;

		if ( ! in_array($callback, Event::$_events[$name], TRUE))
		{
			// Replace the exisiting event with the new event
			Event::$_events[$name][$key] = $callback;
		}
		else
		{
			// Remove the existing event from the queue
			unset(Event::$_events[$name][$key]);

			// Reset the array so the keys are ordered properly
			Event::$_events[$name] = array_values(Event::$_events[$name]);
		}

		return TRUE;
	}

	/**
	 * Get all callbacks for an event.
	 *
	 * @param   string  event name
	 * @return  array
	 */
	public static function get($name)
	{
		return empty(Event::$_events[$name]) ? array() : Event::$_events[$name];
	}

	/**
	 * Clear some or all callbacks from an event.
	 *
	 * @param   string  event name
	 * @param   array   specific callback to remove, FALSE for all callbacks
	 * @return  void
	 */
	public static function clear($name, $callback = FALSE)
	{
		if ($callback === FALSE)
		{
			Event::$_events[$name] = array();
		}
		elseif (isset(Event::$_events[$name]))
		{
			// Loop through each of the event callbacks and compare it to the
			// callback requested for removal. The callback is removed if it
			// matches.
			foreach (Event::$_events[$name] as $i => $event_callback)
			{
				if ($callback === $event_callback)
				{
					unset(Event::$_events[$name][$i]);
				}
			}
		}
	}

	/**
	 * Execute all of the callbacks attached to an event.
	 *
	 * @param   string   event name
	 * @param   array    data can be processed as Event::$data by the callbacks
	 * @return  void
	 */
	public static function run($name, & $data = NULL)
	{

		if ( ! empty(Event::$_events[$name]))
		{
			// Store this so we can run inner events
			Event::$_stack[] =& $data;

			// So callbacks can access Event::$data
			Event::$data =& $data;
			$callbacks  =  Event::get($name);

			foreach ($callbacks as $callback)
			{
				call_user_func($callback);
			}

			// We don't need $data anymore
			array_pop(Event::$_stack);

			// Restore the previous $data value
			$previous_data = end(Event::$_stack);
			Event::$data =& $previous_data;
		}

		// The event has been run!
		Event::$_has_run[$name] = $name;
	}

	/**
	 * Check if a given event has been run.
	 *
	 * @param   string   event name
	 * @return  boolean
	 */
	public static function has_run($name)
	{
		return isset(Event::$_has_run[$name]);
	}

} // End Kohana_Event