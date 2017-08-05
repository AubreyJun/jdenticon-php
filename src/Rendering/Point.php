<?php
namespace Jdenticon\Rendering;

/**
 * A 2D coordinate.
 */
class Point
{
    /**
     * Creates a new Point.
     *
     * @param float $x X coordinate.
     * @param float $y Y coordinate.
     */
    public function __construct($x, $y)
    {
        $this->x = $x;
        $this->y = $y;
    }

    /**
     * The X coordinate of this point.
     *
     * @var float
     */
    public $x;

    /**
     * The Y coordinate of this point.
     *
     * @var float
     */
    public $y;

    /**
     * Gets a string representation of the point.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->x + ", " + $this->y;
    }
}
