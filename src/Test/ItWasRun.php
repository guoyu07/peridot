<?php
namespace Peridot\Test;

use Peridot\Core\Scope;
use Peridot\Core\Spec;

/**
 * Class ItWasRun the first of the specs
 * @package Peridot\Test
 */
class ItWasRun extends Spec
{
    /**
     * @param string $description
     * @param callable $definition
     */
    public function __construct($description, callable $definition)
    {
        parent::__construct($description, $definition);
        $this->getScope()->wasRun = false;
        $this->getScope()->log = false;
    }

    public function wasRun()
    {
        return $this->getScope()->wasRun;
    }

    public function log()
    {
        return $this->getScope()->log;
    }
}
