<?php


/*************************************************************************
* Cache
*
* Caches data, gets cached data
*
* @author Nathan Kowald
* @version 1.0
*
************************************************************************/


class Cache1 {

    private static $cache_dir;
    private static $cache_file;
    private static $cache_life_secs;
    
    /**
    * init()
    * Initialises settings
    *
    * @staticvar string $cache_dir         Set a directory to save cache files to e.g. /home/user/public_html/cache/
    *                                      If left blank it will save cache files to the calling directory (messier)
    *
    * @staticvar integer $cache_life_secs  Time (in seconds) that cache files should last for
    * @staticvar string $cache_file        Builds the cache_file string.
    */
    public static function init($cache_filename='', $cache_life='') {
        global $CFG;
        // Putting the BKSB cache folder in the root of MoodleData so cache files aren't deleted during upgrades etc.
        // We want cache files to last for the lifetime that's set in BKSB settings
        self::$cache_dir = $CFG->dataroot . '/bksb_cache/';
        if (!file_exists(self::$cache_dir) || !is_dir(self::$cache_dir)) {
            // Create bksb folder
            mkdir($CFG->dataroot . '/bksb_cache/', 0755);
        }
        if (self::$cache_dir == '') {
            $path = pathinfo(getcwd());
            self::$cache_dir = $path['dirname'] . '/' . $path['basename'] . '/';
        }
        self::$cache_life_secs = $cache_life;
        self::$cache_file = self::$cache_dir . $cache_filename;
    }
    
    /**
    * cacheFileExists()
    * Checks that the initialised cache_file exists on the server and is less than $cache_life_secs old
    *
    * @return bool True if cache_file exists: False if not
    */
    public static function cacheFileExists() {
        if (file_exists(self::$cache_file) && (filemtime(self::$cache_file) > (time() - self::$cache_life_secs))) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
    * getCache()
    * Gets the contents of the initialised cache file.
    *
    * @return mixed Returns the data stored in the cache file
    */
    public static function getCache() {
        $data = file_get_contents(self::$cache_file);
        return unserialize($data);
    }
    
    /**
    * setCache()
    * Creates the cache file, stores data in it.
    *
    * @param mixed $data Data you want to store in a cache file
    */
    public static function setCache($data='') {
        $cache_data = serialize($data);
        file_put_contents(self::$cache_file, $cache_data);
    }

    public static function clearCache() {
        global $CFG;
        self::$cache_dir = $CFG->dataroot . '/bksb_cache/';
        $files = glob(self::$cache_dir . '*.cache');
        foreach ($files as $file) unlink($file);
    }
}

?>