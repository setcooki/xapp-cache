<?php

defined('XAPP') || require_once(dirname(__FILE__) . '/../../core/core.php');

xapp_import('xapp.Cache.Exception');

/**
 * Cache base class
 *
 * @package Cache
 * @class Xapp_Cache
 * @error 153
 * @author Frank Mueller <set@cooki.me>
 */
abstract class Xapp_Cache
{
    /**
     * cache option value default expiration when cache key expires after
     *
     * @const DEFAULT_EXPIRATION
     */
    const DEFAULT_EXPIRATION            = 'XAPP_CACHE_DEFAULT_EXPIRATION';


    /**
     * contains the current active last created instance either with instance or factory
     * method
     *
     * @var null|Xapp_Cache
     */
    protected static $_instance = null;

    /**
     * contains all singleton instances defined by a names space string identifier create
     * by instance method
     *
     * @var array
     */
    protected static $_instances = array();

    /**
     * options dictionary for this class containing all data type values
     *
     * @var array
     */
    public static $optionsDict = array
    (
        self::DEFAULT_EXPIRATION        => XAPP_TYPE_INT
    );

    /**
     * options mandatory map for this class contains all mandatory values
     *
     * @var array
     */
    public static $optionsRule = array
    (
        self::DEFAULT_EXPIRATION        => 1
    );



    /**
     * get and create static driver instance. if the first parameter is null will try to get
     * the current active instance created regardless of the driver. will throw error if no instance has
     * been created yet. if the second parameter is a driver string like "file" or "apc" will check
     * if instance has already been created and if not will do so with the passed options in second
     * parameter. this method is the preferred way to create cache instances when using multiple instances
     * with different namespaces and/or driver since the factory method will create an instance but not get
     * it at a later stage. the first parameter can be null meaning no namespace identifier which equals to
     * current instance - should only be used when using one instance!
     *
     * @error 15301
     * @param null|string $ns expects optional instance namespace identifier
     * @param null|string $driver expects the cache driver string
     * @param null|mixed $options expects xapp option array or object
     * @return Xapp_Cache_Driver concrete xapp cache driver implementation instance
     * @throws Xapp_Cache_Exception
     */
    public static function instance($ns = null, $driver = null, $options = null)
    {
        if(func_num_args() > 0)
        {
            //setting
            if($driver !== null)
            {
                self::factory($driver, $options, $ns);
            }
            //getting
            if($ns !== null)
            {
                if(array_key_exists($ns, self::$_instances))
                {
                    return self::$_instances[trim((string)$ns)];
                }else{
                    throw new Xapp_Cache_Exception(xapp_sprintf(_("no cache instance under ns: %s registered"), $ns), 1530102);
                }
            }else{
                return self::$_instance;
            }
        }else{
            //getting
            if(self::$_instance !== null)
            {
                return self::$_instance;
            }else{
                throw new Xapp_Cache_Exception(_("can not get current cache class instance since no instance has been set yet"), 1530101);
            }
        }
    }


    /**
     * factory method for creating cache driver instances. this method is the only way, besides the instance method,
     * to create cache driver instances since concrete driver classes dont permit instantiation directly.
     * first parameter expects the driver string like "file" or "apc" and the second parameter
     * an driver options xapp array or option object. the third parameter can contain a namespace
     * identifier when if set will allow for unlimited instance creation. will throw error if
     * the driver does not exist
     *
     * @error 15302
     * @param string $driver expects the cache driver string
     * @param null|mixed $options expects xapp option array or object
     * @param null|string $ns expects optional ns string identifier
     * @return Xapp_Cache_Driver concrete xapp cache driver implementation instance
     * @throws Xapp_Cache_Exception
     */
    public static function factory($driver, $options = null, $ns = null)
    {
        $class = __CLASS__ . '_Driver_' . ucfirst(trim((string)$driver));
        if(class_exists($class, true))
        {
            if($ns !== null)
            {
                return self::$_instance = self::$_instances[trim((string)$ns)] = new $class($options);
            }else{
                return self::$_instance = new $class($options);
            }
        }else{
            throw new Xapp_Cache_Exception(xapp_sprintf(_("cache driver: %s does not exist"), $driver), 1530201);
        }
    }


    /**
     * check if instance is set. without first argument will check only if > 0 instance is set. with first argument name
     * space identifier will check if instance for identifier is set returning boolean true if so false if not
     *
     * @error 15303
     * @param null|string $ns expects option namespace identifier
     * @return bool
     */
    public static function hasInstance($ns = null)
    {
        if($ns !== null)
        {
            return ((array_key_exists(trim((string)$ns), self::$_instances)) ? true : false);
        }else {
            return ((self::$_instance !== null) ? true : false);
        }
    }


    /**
     * create a hash from a string using the algorithm passed in second parameter
     *
     * @error 15304
     * @param string $string expects the string to hash
     * @param string $algo expects a valid hash algorithm
     * @return string
     */
    public static function hash($string, $algo = 'sha1')
    {
        return hash(strtolower(trim((string)$algo)), trim((string)$string));
    }


    /**
     * call static abstract driver functions like "get", "set" with the valid namespace, driver or current
     * driver instance. if the first parameter is a namespace identifier and ns value is found in array will
     * get the instance stored under the ns. if the first parameter is a driver name string will try to get
     * the instance for that. if no ns or driver name used will use last current instance. NOTE: keys should
     * never be a driver or ns name!
     *
     * @error 15305
     * @param string $method expects the function name
     * @param array $params expects the overloading $params
     * @return mixed
     * @throws Xapp_Cache_Exception
     */
    public static function __callStatic($method, $params)
   	{
        $instance = null;

        if(sizeof($params) >= 2 && array_key_exists((string)$params[0], self::$_instances))
        {
            $instance = self::$_instances[(string)$params[0]];
            $params = array_slice($params, 1);
        }else{
            $instance = self::$_instance;
        }
        if($instance !== null)
        {
            if(method_exists($instance, $method))
            {
                return call_user_func_array(array($instance, $method), $params);
            }else{
                throw new Xapp_Cache_Exception(xapp_sprintf(_("method: %s can not be called statically"), $method), 1530501);
            }
        }else{
            throw new Xapp_Cache_Exception(_("no instance found for static cache class overloading"), 1530502);
        }
   	}


    /**
     * dont allow cloning!
     *
     * @error 15305
     * @return void
     */
    protected function __clone(){}
}