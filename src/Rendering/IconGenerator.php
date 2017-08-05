<?php
namespace Jdenticon\Rendering;

use Jdenticon\Shapes\Shape;
use Jdenticon\Shapes\ShapeCategory;
use Jdenticon\Shapes\ShapeDefinitions;

/**
 * Generates identicons and render them to a 
 * {@link \Jdenticon\Rendering\RendererInterface}. This class dictates what 
 * shapes will be used in the generated icons. If you intend to customize the 
 * appearance of generated icons you probably wants to either subclass or modify 
 * this class.
 */
class IconGenerator
{
    private $defaultShapes;
    private static $instance;
    
    protected function __construct()
    {
        $this->defaultShapes = array(
            // Sides
            new ShapeCategory(
                /*$colorIndex=*/ 8,
                /*$shapes=*/ ShapeDefinitions::getOuterShapes(),
                /*$shapeIndex=*/ 2,
                /*$rotationIndex=*/ 3,
                /*$positions=*/ array(1,0, 2,0, 2,3, 1,3, 0,1, 3,1, 3,2, 0,2)
            ),
            
            // Corners
            new ShapeCategory(
                /*$colorIndex=*/ 9,
                /*$shapes=*/ ShapeDefinitions::getOuterShapes(),
                /*$shapeIndex=*/ 4,
                /*$rotationIndex=*/ 5,
                /*$positions=*/ array(0,0, 3,0, 3,3, 0,3)
            ),
            
            // Center
            new ShapeCategory(
                /*$colorIndex=*/ 10,
                /*$shapes=*/ ShapeDefinitions::getCenterShapes(),
                /*$shapeIndex=*/ 1,
                /*$rotationIndex=*/ null,
                /*$positions=*/ array(1,1, 2,1, 2,2, 1,2)
            )
        );
    }
    
    public static function getDefaultGenerator()
    {
        if (self::$instance === null) {
            self::$instance = new IconGenerator();
        }
        return self::$instance;
    }
    
    /**
     * Gets the number of cells in each direction of the icons generated by 
     * this IconGenerator.
     *
     * @return int
     */
    public function getCellCount()
    {
        return 4;
    }

    /**
     * Determines the hue to be used in an icon for the specified hash.
     *
     * @return float Hue in the range [0, 1].
     */
    protected static function getHue($hash)
    {
        $value = hexdec(substr($hash, -7));
        return $value / 0xfffffff;
    }

    /**
     * Determines whether $newValue is duplicated in $source if all values 
     * in $duplicateValues are determined to be equal.
     *
     * @return bool
     */
    private static function isDuplicate(
        array $source, $newValue, 
        array $duplicateValues)
    {
        if (in_array($newValue, $duplicateValues, true)) {
            foreach ($duplicateValues as $value) {
                if (in_array($value, $source, true)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Gets the specified octet from a byte array.
     *
     * @param string $hash The hexstring from which the octet will be retrieved.
     * @param int $index The zero-based index of the octet to be returned.
     * @return int
     */
    protected static function getOctet($hash, $index)
    {
        return hexdec($hash[$index]);
    }

    /**
     * Gets an array of the shape categories to be rendered in icons generated 
     * by this IconGenerator.
     *
     * @return array
     */
    protected function getCategories()
    {
        return $this->defaultShapes;
    }

    /**
     * Gets an enumeration of individual shapes to be rendered in an icon for a 
     * specific hash.
     * 
     * @param \Jdenticon\Rendering\ColorTheme $colorTheme A color theme 
     *      specifying the colors to be used in the icon.
     * @param string $hash The hash for which the shapes will be returned.
     * @return array(Jdenticon\Shapes\Shape)
     */
    protected function getShapes($colorTheme, $hash)
    {
        $usedColorThemeIndexes = array();
        $categories = self::getCategories();
        $shapes = array();
        $colorCount = $colorTheme->getCount();
        
        foreach ($categories as $category) {
            $colorThemeIndex = 
                self::getOctet($hash, $category->colorIndex) % $colorCount;

            if (self::isDuplicate(
                    // Disallow dark gray and dark color combo
                    $usedColorThemeIndexes, $colorThemeIndex, array(0, 4)) || 
                self::isDuplicate(
                    // Disallow light gray and light color combo
                    $usedColorThemeIndexes, $colorThemeIndex, array(2, 3))
            ) {
                $colorThemeIndex = 1;
            }

            $usedColorThemeIndexes[] = $colorThemeIndex;

            $startRotationIndex = $category->rotationIndex === null ? 
                0 : self::getOctet($hash, $category->rotationIndex);
            $shapeIndex = 
                self::getOctet($hash, $category->shapeIndex) % 
                count($category->shapes);
            $shape = $category->shapes[$shapeIndex];
            
            $shapes[] = new Shape(
                /*$definition=*/ $shape,
                /*$color=*/ $colorTheme->getByIndex($colorThemeIndex),
                /*$positions=*/ $category->positions,
                /*$startRotationIndex=*/ $startRotationIndex
            );
        }
        
        return $shapes;
    }

    /**
     * Creates a quadratic copy of the specified 
     * {@link \Jdenticon\Rendering\Rectangle} with a multiple of the cell count 
     * as size.
     *
     * @param \Jdenticon\Rendering\Rectangle $rect The rectangle to be 
     *      normalized.
     */
    protected function normalizeRectangle(\Jdenticon\Rendering\Rectangle $rect)
    {
        $size = (int)min($rect->width, $rect->height);
        
        // Make size a multiple of the cell count
        $size -= $size % $this->getCellCount();
        
        return new Rectangle(
            (int)($rect->x + ($rect->width - $size) / 2),
            (int)($rect->y + ($rect->height - $size) / 2),
            $size,
            $size);
    }

    /**
     * Renders the background of an icon.
     *
     * @param \Jdenticon\Rendering\RendererInterface $renderer The renderer to 
     *      be used for rendering the icon on the target surface.
     * @param \Jdenticon\Rendering\Rectangle $rect The outer bounds of the icon.
     * @param \Jdenticon\IdenticonStyle $style The style of the icon.
     * @param \Jdenticon\Rendering\ColorTheme $colorTheme A color theme 
     *      specifying the colors to be used in the icon.
     * @param string $hash The hash to be used as basis for the generated icon.
     */
    protected function renderBackground(
        \Jdenticon\Rendering\RendererInterface $renderer, 
        \Jdenticon\Rendering\Rectangle $rect,
        \Jdenticon\IdenticonStyle $style, 
        \Jdenticon\Rendering\ColorTheme $colorTheme, 
        $hash)
    {
        $renderer->setBackgroundColor($style->getBackgroundColor());
    }
    
    /**
     * Renders the foreground of an icon.
     *
     * @param \Jdenticon\Rendering\RendererInterface $renderer The renderer to 
     *      be used for rendering the icon on the target surface.
     * @param \Jdenticon\Rendering\Rectangle $rect The outer bounds of the icon.
     * @param \Jdenticon\IdenticonStyle $style The style of the icon.
     * @param \Jdenticon\Rendering\ColorTheme $colorTheme A color theme 
     *      specifying the colors to be used in the icon.
     * @param string $hash The hash to be used as basis for the generated icon.
     */
    protected function renderForeground(
        \Jdenticon\Rendering\RendererInterface $renderer, 
        \Jdenticon\Rendering\Rectangle $rect,
        \Jdenticon\IdenticonStyle $style, 
        \Jdenticon\Rendering\ColorTheme $colorTheme, 
        $hash)
    {
        // Ensure rect is quadratic and a multiple of the cell count
        $normalizedRect = $this->normalizeRectangle($rect);
        $cellSize = $normalizedRect->width / $this->getCellCount();

        foreach ($this->getShapes($colorTheme, $hash) as $shape) {
            $rotation = $shape->startRotationIndex;
            
            $renderer->beginShape($shape->color);
            
            $positionCount = count($shape->positions);
            for ($i = 0; $i + 1 < $positionCount; $i += 2) {
                $renderer->setTransform(new Transform(
                    $normalizedRect->x + $shape->positions[$i + 0] * $cellSize,
                    $normalizedRect->y + $shape->positions[$i + 1] * $cellSize,
                    $cellSize, $rotation++ % 4));

                $shape->definition->__invoke($renderer, $cellSize, $i / 2);
            }
            
            $renderer->endShape();
        }
    }

    /**
     * Generates an identicon for the specified hash.
     *
     * @param \Jdenticon\Rendering\RendererInterface $renderer The renderer to 
     *      be used for rendering the icon on the target surface.
     * @param \Jdenticon\Rendering\Rectangle $rect The outer bounds of the icon.
     * @param \Jdenticon\IdenticonStyle $style The style of the icon.
     * @param string $hash The hash to be used as basis for the generated icon.
     */
    public function generate(
        \Jdenticon\Rendering\RendererInterface $renderer, 
        \Jdenticon\Rendering\Rectangle $rect,
        \Jdenticon\IdenticonStyle $style, 
        $hash)
    {
        $hue = self::getHue($hash);
        $colorTheme = new ColorTheme($hue, $style);

        $this->renderBackground($renderer, $rect, $style, $colorTheme, $hash);
        $this->renderForeground($renderer, $rect, $style, $colorTheme, $hash);
    }
}
