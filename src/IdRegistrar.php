<?php namespace FewAgency\FluentHtml;

/**
 * Keeps track of used id strings.
 * Appends or increments a counter at the end of id strings should the requested id already be taken.
 * Just pass all suggested ids through the unique() method and use the return value.
 *
 * Use getGlobalInstance() to access a static global instance of the class.
 * This class can also be instantiated several times if you need separate collections of ids within the same script.
 */
class IdRegistrar
{
    /**
     * @var IdRegistrar The reference to a global instance of this class
     */
    protected static $global_instance;

    /**
     * @var array values are taken id's
     */
    protected $repository = [];

    /**
     * Get a global instance of the class.
     * Useful for keeping ids unique across the currently running PHP script.
     *
     * @return IdRegistrar
     */
    public static function getGlobalInstance()
    {
        if (empty(static::$global_instance)) {
            static::$global_instance = new static();
        }

        return static::$global_instance;
    }

    /**
     * @param string|null $desired_id to check if taken
     * @return string id string that is unique in the global registrar
     */
    public static function globalUnique($desired_id = null)
    {
        return static::getGlobalInstance()->unique($desired_id);
    }

    /**
     * Pass all desired id-strings through this method and use the return value.
     *
     * @param string $desired_id to check if taken
     * @return string id to use, guaranteed to be unique in this registrar
     */
    public function unique($desired_id)
    {
        if (empty($desired_id)) {
            $message = __METHOD__ . "() doesn't accept empty parameter ($desired_id)";
            throw new \InvalidArgumentException($message);
        }
        if ($this->add($desired_id)) {
            //The id was not taken, now it is registered - use it!
            return $desired_id;
        }
        //Isolate any appended section of digits
        preg_match('/^(.+?)(\d+)?$/', $desired_id, $matches);
        $base_id = $matches[1];
        $next_number = max(isset($matches[2]) ? $matches[2] : 0, 1) + 1;

        //Recursively try the next numbered id
        return $this->unique($base_id . $next_number);
    }

    /**
     * Check if an id has been used
     *
     * @param string $id
     * @return bool true if the id is already used
     */
    public function exists($id)
    {
        return $this->has($id);
    }

    /**
     * Determine if an id exists in the repository
     *
     * @param string $id
     * @return bool
     */
    protected function has($id)
    {
        return in_array($id, $this->repository);
    }

    /**
     * Store an id in the repository if it does not exist
     *
     * @param string $id
     * @return bool
     */
    protected function add($id)
    {
        if (!$this->has($id)) {
            $this->repository[] = $id;

            return true;
        }

        return false;
    }
}