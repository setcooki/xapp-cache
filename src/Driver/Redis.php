<?php

defined('XAPP') || require_once(dirname(__FILE__) . '/../../../core/core.php');

xapp_import('xapp.Cache.Driver');
xapp_import('xapp.Cache');
xapp_import('xapp.Cache.Driver.Exception');
xapp_import('xapp.Cache.Exception');

/**
 * Cache driver file class
 *
 * @package Cache
 * @subpackage Cache_Driver
 * @class Xapp_Cache_Driver_Redis
 * @error 175
 * @author Frank Mueller <set@cooki.me>
 */
class Xapp_Cache_Driver_Redis extends Xapp_Cache_Driver
{
    /**
     * The redis instance if set outside of class
     */
    const INSTANCE                      = 'XAPP_CACHE_DRIVER_REDIS_INSTANCE';

    /**
     * The redis host ip or string
     */
    const HOST                          = 'XAPP_CACHE_DRIVER_REDIS_HOST';

    /**
     * The port on where to connect
     */
    const PORT                          = 'XAPP_CACHE_DRIVER_REDIS_PORT';

    /**
     * The connection timeout
     */
    const TIMEOUT                       = 'XAPP_CACHE_DRIVER_REDIS_TIMEOUT';

    /**
     * The password if needed
     */
    const PASSWORD                      = 'XAPP_CACHE_DRIVER_REDIS_PASSWORD';

    /**
     * The db index if set
     */
    const DB                            = 'XAPP_CACHE_DRIVER_REDIS_DB';

    /**
     * Redis options as array
     */
    const OPTIONS                       = 'XAPP_CACHE_DRIVER_REDIS_OPTIONS';


    /**
     * contains the singleton instance for this class
     *
     * @var null|Xapp_Cache_Driver_Redis
     */
    protected static $_instance = null;


    /**
     * contains the redis instance created for this cache instance
     *
     * @var null
     */
    protected $_redis = null;


    /**
     * options dictionary for this class containing all data type values
     *
     * @var array
     */
    public static $optionsDict = array
    (
        self::INSTANCE              => 'Redis',
        self::HOST                  => XAPP_TYPE_STRING,
        self::PORT                  => XAPP_TYPE_INT,
        self::TIMEOUT               => XAPP_TYPE_FLOAT,
        self::PASSWORD              => XAPP_TYPE_STRING,
        self::DB                    => XAPP_TYPE_INT,
        self::OPTIONS               => XAPP_TYPE_ARRAY
    );

    /**
     * options mandatory map for this class contains all mandatory values
     *
     * @var array
     */
    public static $optionsRule = array
    (
        self::INSTANCE              => 0,
        self::HOST                  => 1,
        self::PORT                  => 1,
        self::TIMEOUT               => 0,
        self::PASSWORD              => 0,
        self::DB                    => 0,
        self::OPTIONS               => 0
    );

    /**
     * options default value array containing all class option default values
     *
     * @var array
     */
    public $options = array
    (
        self::TIMEOUT               => 5
    );



    /**
     * static singleton method to create static instance of driver with optional third parameter
     * xapp options array or object
     *
     * @error 175001
     * @param null|mixed $options expects optional xapp option array or object
     * @return Xapp_Cache_Driver_Redis
     */
    public static function instance($options = null)
    {
        if(self::$_instance === null)
        {
            self::$_instance = new self($options);
        }
        return self::$_instance;
    }


    /**
     * init apc instance by checking for apc extension
     *
     * @error 175002
     * @throws Xapp_Cache_Driver_Exception
     * @returns void
     */
    protected function init()
    {
        if(!extension_loaded('redis'))
        {
            throw new Xapp_Cache_Driver_Exception(__("redis is not supported by this system"), 17500201);
      	}
        if(class_exists('Redis'))
        {
            try
            {
                if(xapp_is_option(self::INSTANCE, $this))
                {
                    $this->_redis = xapp_get_option(self::INSTANCE, $this);
                }else{
                    $this->_redis = new Redis();
                    $this->_redis->connect
                    (
                        xapp_get_option(self::HOST, $this),
                        xapp_get_option(self::PORT, $this),
                        xapp_get_option(self::TIMEOUT, $this)
                    );
                    if(xapp_is_option(self::OPTIONS, $this))
                    {
                        foreach(xapp_get_option(self::OPTIONS, $this, array()) as $key => $val)
                        {
                            if(!$this->_redis->setOption($key, $val))
                            {
                                throw new Xapp_Cache_Driver_Exception(xapp_sprintf(__("redis set option: %s failed"), $key), 17500205);
                            }
                        }
                    }
                    if(xapp_is_option(self::PASSWORD, $this))
                    {
                        if(!$this->_redis->auth(xapp_get_option(self::PASSWORD, $this)))
                        {
                            throw new Xapp_Cache_Driver_Exception(__("redis auth not successful"), 17500203);
                        }
                    }
                    if(xapp_has_option(self::DB, $this))
                    {
                        if(!$this->_redis->select(xapp_get_option(self::DB)))
                        {
                            throw new Xapp_Cache_Driver_Exception(__("redis select db not successful"), 17500204);
                        }
                    }
                }
            }
            catch(RedisException $e)
            {
                throw new Xapp_Cache_Driver_Exception(xapp_sprintf(__("unable to connect to redis: %s"), $e->getMessage()), 17500202);
            }
        }
    }


    /**
     * get value for cache key returning default value if key does not exist or is expired
     *
     * @error 175003
     * @param string $key expects the cache key name as string
     * @param null|mixed $default expects the default return value if cache key does not exist anymore
     * @return mixed|null
     * @throws \Exception
     */
    public function get($key, $default = null)
    {
        if(($value = $this->_redis->get($key)) !== false)
        {
            return $value;
        }else{
            return xapp_default($default);
        }
    }


    /**
     * set value for cache key with optional lifetime value as third parameter. if not set default lifetime
     * will be applied. returns boolean true if success else false
     *
     * @error 175004
     * @param string $key expects the cache key name as string
     * @param mixed $value expects the value to set for cache key
     * @param null|int $lifetime expects the optional lifetime value
     * @return boolean
     */
    public function set($key, $value, $lifetime = null)
    {
        return (bool)$this->_redis->set($key, $value, $lifetime);
    }


    /**
     * check if cache key still exists or has been purged already returning boolean value
     *
     * @error 175005
     * @param string $key expects the cache key name as string
     * @return bool
     */
    public function has($key)
    {
        return ((int)$this->_redis->exists($key) === 0) ? false : true;
    }


    /**
     * remove cache key returning boolean value
     *
     * @error 175006
     * @param string $key expects the cache key name as string
     * @return bool
     */
    public function forget($key)
    {
        if($this->has($key))
        {
            return $this->_redis->delete($key);
        }else{
            return false;
        }
    }


    /**
     * flush all key values which are left in key store
     *
     * @error 175007
     * @param bool $expired not used for apc implementation
     * @return bool
     */
    public function purge($expired = true)
    {
        return $this->_redis->flushDb();
    }


    /**
     * method to set/get redis instance. when setting will overwrite previously set redis instance so
     * passed instance should be instantiated will all required options etc.
     *
     * @error 175008
     * @param Redis $redis expects optional redis instance when setting
     * @return Redis|null
     */
    public function redis(Redis $redis = null)
    {
        if($redis !== null)
        {
            return $this->_redis = xapp_set_option(self::INSTANCE, $redis, $this, true);
        }else{
            return $this->_redis;
        }
    }
}