<?php
namespace Jdenticon\Shapes;

/**
 * Represents a shape to be rendered in an icon. These instances are 
 * hash specific.
 */
class Shape
{
    /**
     * The shape definition to be used to render the shape.
     *
     * @var function(
     *      \Jdenticon\Rendering\RendererInterface $renderer, 
     *      \Jdenticon\Shapes\ShapePosition $cell, 
     *      int $index)
     */
    public $definition;

    /**
     * The fill color of the shape.
     *
     * @var Jdenticon\Rendering\Color
     */
    public $color;

    /**
     * The positions in which the shape will be rendered.
     * 
     * @var array(\Jdenticon\Shapes\ShapePosition)
     */
    public $positions;

    /**
     * The rotation index of the icon in the first position.
     *
     * @var int
     */
    public $startRotationIndex;
    
    public function __construct(
        $definition, 
        \Jdenticon\Rendering\Color $color, 
        array $positions, 
        $startRotationIndex)
    {
        $this->definition = $definition;
        $this->color = $color;
        $this->positions = $positions;
        $this->startRotationIndex = $startRotationIndex;
    }
}
