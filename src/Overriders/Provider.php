<?php
/**
 * 2020-present Friends of Presta community
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT License
 * that is bundled with this package in the file LICENSE
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/MIT
 *
 * @author Friends of Presta community
 * @copyright 2020-present Friends of Presta community
 * @license https://opensource.org/licenses/MIT MIT
 */

namespace FOP\Console\Overriders;

class Provider
{
    /**
     * @var array<OverriderInterface>
     */
    private $overriders = [];

    /**
     * Provider constructor.
     *
     * @param array<OverriderInterface> $overriders
     *
     * @throws \Exception
     */
    public function __construct(array $overriders)
    {
        // check that provided Overriders are really Overriders.
        array_walk($overriders, function ($overrider) {
            if (!$overrider instanceof OverriderInterface) {
                throw new \Exception(__CLASS__ . ' parameter $overrider must contain ' . OverriderInterface::class . ' instances only.');
            }
        });
        $this->overriders = $overriders;
    }

    /**
     * Returns the overriders which handle this path.
     *
     * @param string $path
     *
     * @return OverriderInterface[]
     */
    public function getOverriders(string $path): array
    {
        return array_filter($this->overriders, function ($overrider) use ($path) {return $overrider->handle($path); });
    }
}
