<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitec912013a389ea50d29dd9fa60cfa4cb
{
    public static $prefixLengthsPsr4 = array (
        'D' => 
        array (
            'Defuse\\Crypto\\' => 14,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Defuse\\Crypto\\' => 
        array (
            0 => __DIR__ . '/..' . '/defuse/php-encryption/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitec912013a389ea50d29dd9fa60cfa4cb::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitec912013a389ea50d29dd9fa60cfa4cb::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitec912013a389ea50d29dd9fa60cfa4cb::$classMap;

        }, null, ClassLoader::class);
    }
}
