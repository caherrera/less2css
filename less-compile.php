<?php
/**
 * Created by PhpStorm.
 * User: carlosherrera
 * Date: 9/11/17
 * Time: 11:21 AM
 */
if ( ! class_exists('lessc')) {
    if (!file(__DIR__ . '/vendor/autoload.php')) {
        wp_die('you need to run composer to use this plugin');
    }
    include_once(__DIR__ . '/vendor/autoload.php');
}

class LessCompiler
{
    protected $inputFile;
    protected $outputFile;

    function __construct($inputFile = '', $outputFile = '')
    {
        $this->inputFile  = $this->sanitizeInput($inputFile);
        $this->outputFile = $this->sanitizeOutput($outputFile);

    }

    public function sanitizeInput($inputFile = '')
    {
        return $inputFile ?: (get_stylesheet_directory() . '/style.less');
    }

    public function sanitizeOutput($outputFile = '')
    {
        return $outputFile ?: (get_stylesheet_directory() . '/style.css');
    }

    public function compile()
    {
        if ($this->detectChanges()) {
            $this->doCompile();
        }
    }

    function detectChanges()
    {
        if ( ! file_exists($this->inputFile)) {
            return null;
        }
        if ( ! file_exists($this->inputFile . '.cache')) {
            return true;
        }
        if ( ! file_exists($this->outputFile)) {
            return true;
        }

        if (filemtime($this->inputFile) > filemtime($this->outputFile)) {
            return true;
        }

        return false;
    }

    private function doCompile()
    {
        global $variablesArray;

        if (empty($variablesArray)) {
            $variablesArray = [];
        }


        // load the cache
        $cacheFile = $this->inputFile . ".cache";


        if (file_exists($cacheFile)) {
            $cache = unserialize(file_get_contents($cacheFile));
        } else {
            $cache = $this->inputFile;
        }

        // custom formatter
        $formatter = new lessc_formatter_compressed();

        $less = new lessc;
        $less->setVariables($variablesArray);
        $less->setFormatter($formatter);

        try {
            // create a new cache object, and compile
            $newCache = $less->cachedCompile($cache);

            // the next time we run, write only if it has updated
            if ( ! is_array($cache) || $newCache["updated"] > $cache["updated"]) {
                file_put_contents($cacheFile, serialize($newCache));
                file_put_contents($this->outputFile, $newCache['compiled']);
            }
        } catch (Exception $ex) {
            echo "lessphp fatal error: " . $ex->getMessage();
        }
    }

    /**
     * @param string $inputFile
     *
     * @return LessCompiler
     */
    public function setInputFile(string $inputFile): LessCompiler
    {
        $this->inputFile = $inputFile;

        return $this;
    }

    /**
     * @param string $outputFile
     *
     * @return LessCompiler
     */
    public function setOutputFile(string $outputFile): LessCompiler
    {
        $this->outputFile = $outputFile;

        return $this;
    }
}
