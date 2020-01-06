<?php

defined('XAPP') || require_once(dirname(__FILE__) . '/../../core/core.php');

xapp_import('xapp.Cache');
xapp_import('xapp.Cache.Driver');
xapp_import('xapp.Cache.Driver.Exception');

/**
 * Cache driver base class
 *
 * @package Cache
 * @class Xapp_Cache_Driver
 * @error 154
 * @author Frank Mueller <set@cooki.me>
 */
abstract class Xapp_Cache_Driver
{
    /**
     * option to auto purge all expired items in class destructor
     *
     * @const AUTOPURGE_EXPIRED
     */
    const AUTOPURGE_EXPIRED         = 'XAPP_CACHE_DRIVER_AUTOPURGE_EXPIRED';    
    
    
    /**
     * cache option value default expiration when cache key expires after
     *
     * @const DEFAULT_EXPIRATION
     */
    const DEFAULT_EXPIRATION            = 'XAPP_CACHE_DEFAULT_EXPIRATION';


    /**
     * options dictionary for this class containing all data type values
     *
     * @var array
     */
    public static $optionsDict = array
    (
        self::AUTOPURGE_EXPIRED     => XAPP_TYPE_BOOL,
        self::DEFAULT_EXPIRATION    => XAPP_TYPE_INT
    );

    /**
     * options mandatory map for this class contains all mandatory values
     *
     * @var array
     */
    public static $optionsRule = array
    (
        self::AUTOPURGE_EXPIRED     => 0,
        self::DEFAULT_EXPIRATION    => 1
    );



    /**
     * init concrete driver cache implementation
     *
     * @return void
     */
    abstract protected function init();


    /**
     * get cache key
     *
     * @param $key
     * @param null $default
     * @return mixed
     */
    abstract public function get($key, $default = null);


    /**
     * set cache key
     *
     * @param $key
     * @param $value
     * @param null $lifetime
     * @return boolean
     */
    abstract public function set($key, $value, $lifetime = null);


    /**
     * check if cache key exists
     *
     * @param $key
     * @return bool
     */
    abstract public function has($key);


    /**
     * remove key from cache
     *
     * @param $key
     * @return bool
     */
    abstract public function forget($key);


    /**
     * purge/flush expired keys or all keys
     *
     * @param bool $expired
     * @return void|bool
     */
    abstract public function purge($expired = true);


    /**
     * default class constructor for all driver implementations that need no
     * special constructor. concrete driver implementations should always call
     * parent constructor
     *
     * @error 15401
     * @param null|mixed $options expects optional xapp option array or object
     */
    public function __construct($options = null)
    {
        xapp_init_options($options, $this);
        $this->init();
    }


    /**
     * make and return timestamp for now or now + x seconds if second parameter is not null
     *
     * @error 15402
     * @param null|int $seconds expects additional value of seconds to add to timestamp now
     * @return int
     */
    protected function timestamp($seconds = null)
    {
        if($seconds !== null)
        {
            return time() + (int)$seconds;
        }else{
            return time();
        }
    }


    /**
     * overload class by setting cache key => value pair with default lifetime value
     *
     * @error 15403
     * @param string $name expects the key name
     * @param null|mixed $value expects the value to set
     * @return null|mixed
     */
    public function __set($name, $value = null)
    {
        if($value === null)
        {
            return null;
        }
        return $this->set($name, $value);
    }


    /**
     * overload class by getting value for cache key. will return null if the key does
     * not exist.
     *
     * @error 15404
     * @param string $name expects the key name
     * @return null|mixed
     */
    public function __get($name)
    {
        if($this->has($name))
        {
            return $this->get($name);
        }else{
            return null;
        }
    }


    /**
     * overload class by checking for existing cache key returning boolean value
     *
     * @error 15405
     * @param string $name expects the key name
     * @return bool
     */
    public function __isset($name)
    {
        return $this->has($name);
    }


    /**
     * overload class by un setting cached entry with key name in first parameter
     *
     * @error 15406
     * @param string $name expects the key name
     * @return bool
     */
    public function __unset($name)
    {
        return $this->forget($name);
    }


    /**
     * will auto purge all expired items if AUTOPURGE_EXPIRED option is set to true
     *
     * @error 15406
     * @return void
     */
    public function __destruct()
    {
        if(xapp_get_option(self::AUTOPURGE_EXPIRED, $this))
        {
            $this->purge(true);
        }
    }
}