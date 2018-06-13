<?php

namespace slim\support;

class File
{

    /**
     * getPathList
     *
     * @param                            $dir
     * @param                            int 0 all path and files  $depth
     *
     * @return array
     */
    public static function getPathList($dir, $depth = 0)
    {
        $baseDir = $dir;
        $list    = function($dir, $depth, $baseDir) use (&$list) {
            $files = [];
            if (@$handle = opendir($dir)) {
                while (($file = readdir($handle)) !== false) {
                    if ($file != ".." && $file != ".") {
                        if (is_dir($dir . "\\" . $file)) {
                            $path     = str_replace($baseDir, '', realpath($dir . "\\" . $file));
                            $nowDepth = count(explode('\\', $path));
                            if ($depth === 0 or $nowDepth < $depth) {
                                if (empty($files[$file] = $list($dir . $file . "\\", $depth, $baseDir))) {
                                    unset($files[$file]);
                                }
                            }
                        } else {
                            $files[] = $dir . $file;
                        }
                    }
                }
                closedir($handle);

                return $files;
            }
        };

        return $list($dir, $depth, $baseDir);
    }

    /**
     * array_depth
     *
     * @param $array
     *
     * @return int
     */
    public static function array_depth($array)
    {
        $max_deep = 1;
        foreach ($array as $value) {
            if (is_array($value)) {
                $deep = self::array_depth($value) + 1;
                // 递归完毕后，判断每次递归的深度是否大于当前的最大深度
                if ($deep > $max_deep) {
                    $max_deep = $deep;
                }
            }
        };

        return $max_deep;
    }

}