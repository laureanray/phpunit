<?php declare(strict_types=1);
/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PHPUnit\Runner\Extension;

use function class_exists;
use function sprintf;
use PHPUnit\Framework\Exception;
use PHPUnit\Framework\TestListener;
use PHPUnit\Runner\Hook;
use PHPUnit\TextUI\XmlConfiguration\Extension;
use ReflectionClass;
use ReflectionException;

/**
 * @internal This class is not covered by the backward compatibility promise for PHPUnit
 */
final class ExtensionHandler
{
    public function createInstance(Extension $extension): Hook
    {
        $object = $this->doCreateInstance($extension);

        if (!$object instanceof Hook) {
            throw new Exception(
                sprintf(
                    'Class "%s" does not implement a PHPUnit\Runner\Hook interface',
                    $extension->className()
                )
            );
        }

        return $object;
    }

    /**
     * @deprecated
     */
    public function createLegacyInstance(Extension $extension): TestListener
    {
        $object = $this->doCreateInstance($extension);

        if (!$object instanceof TestListener) {
            throw new Exception(
                sprintf(
                    'Class "%s" does not implement the PHPUnit\Framework\TestListener interface',
                    $extension->className()
                )
            );
        }

        return $object;
    }

    private function doCreateInstance(Extension $extension): object
    {
        $this->ensureClassExists($extension);

        try {
            $reflector = new ReflectionClass($extension->className());
        } catch (ReflectionException $e) {
            throw new Exception(
                $e->getMessage(),
                (int) $e->getCode(),
                $e
            );
        }

        if (!$extension->hasArguments()) {
            return $reflector->newInstance();
        }

        return $reflector->newInstanceArgs($extension->arguments());
    }

    /**
     * @throws Exception
     */
    private function ensureClassExists(Extension $extension): void
    {
        if (class_exists($extension->className(), false)) {
            return;
        }

        if ($extension->hasSourceFile()) {
            /**
             * @noinspection PhpIncludeInspection
             * @psalm-suppress UnresolvableInclude
             */
            require_once $extension->sourceFile();
        }

        if (!class_exists($extension->className())) {
            throw new Exception(
                sprintf(
                    'Class "%s" does not exist',
                    $extension->className()
                )
            );
        }
    }
}
