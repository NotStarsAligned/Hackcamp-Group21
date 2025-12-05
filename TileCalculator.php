<?php

class TileCalculator
{
    private $length;
    private $width;

    public function __construct($length, $width)
    {
        // Cast to float just to be safe
        $this->length = (float) $length;
        $this->width  = (float) $width;
    }

    // Calculate area (length x width)
    public function getArea()
    {
        return $this->length * $this->width;
    }

    // Return all info in an array
    public function getSummary()
    {
        return [
            'length' => $this->length,
            'width'  => $this->width,
            'area'   => $this->getArea(),
        ];
    }
}
