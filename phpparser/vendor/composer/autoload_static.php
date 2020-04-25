<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit7707e55d7aa32d7eed9969d08414dc14
{
    public static $prefixLengthsPsr4 = array (
        'P' => 
        array (
            'PhpParser\\' => 10,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'PhpParser\\' => 
        array (
            0 => __DIR__ . '/..' . '/nikic/php-parser/lib/PhpParser',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit7707e55d7aa32d7eed9969d08414dc14::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit7707e55d7aa32d7eed9969d08414dc14::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}