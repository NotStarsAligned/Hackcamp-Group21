<?php

class TileCalculator
{
    // Room dimensions (metres)
    private $length;
    private $width;

    // Tile dimensions (millimetres)
    private $tileLength;
    private $tileWidth;

    // Settings & pricing

    // 10% waste for cuts/breakage
    private $wastePercent = 10.0;

    // Assumption: 4 tiles per box
    private $tilesPerBox  = 4;

    // Pricing (can later come from DB / spreadsheet)
    private $materialCostPerM2 = 20.0; // 20 per m² (placeholder, depends on tile type)
    private $labourCostPerM2   = 10.0; // 10 per m² (from "10 per meter" in meeting notes)
    private $deliveryCost      = 65.0; // 65 (using full pallet rate)
    private $consumablesCost   = 10.0; // 10 flat (adhesive, grout etc.)
    private $taxRate           = 20.0; // 20% tax rate (standard VAT style)

    // Pallet information (not yet used in maths, stored for future)
    private $palletCapacityM2   = 100.0; // 100 m² per pallet
    private $fullPalletDelivery = 65.0;  // full pallet delivery rate
    private $halfPalletDelivery = 55.0;  // half pallet delivery rate

    // Constructor
    public function __construct($length, $width, $tileLength, $tileWidth)
    {
        $this->length     = (float) $length;
        $this->width      = (float) $width;
        $this->tileLength = (int) $tileLength;
        $this->tileWidth  = (int) $tileWidth;
    }

    // Room area in m²
    public function getRoomArea()
    {
        return $this->length * $this->width;
    }

    // Tile area in m² (mm → m)
    public function getTileArea()
    {
        $tLength = $this->tileLength / 1000; // mm to m
        $tWidth  = $this->tileWidth  / 1000;

        return $tLength * $tWidth;
    }

    // Raw tiles needed
    public function getTilesNeededRaw()
    {
        if ($this->getTileArea() <= 0) {
            return 0;
        }

        return (int) ceil($this->getRoomArea() / $this->getTileArea());
    }

    // Tiles needed including waste
    public function getTilesNeededWithWaste()
    {
        $raw        = $this->getTilesNeededRaw();
        $multiplier = 1 + ($this->wastePercent / 100);

        return (int) ceil($raw * $multiplier);
    }

    // Boxes needed
    public function getBoxesNeeded()
    {
        if ($this->tilesPerBox <= 0) {
            return 0;
        }

        return (int) ceil($this->getTilesNeededWithWaste() / $this->tilesPerBox);
    }

    // Cost calculations
    public function getMaterialCost()
    {
        return $this->getRoomArea() * $this->materialCostPerM2;
    }

    public function getLabourCost()
    {
        // In future: if installation is optional, this can return 0 when not selected.
        return $this->getRoomArea() * $this->labourCostPerM2;
    }

    public function getSubtotal()
    {
        return
            $this->getMaterialCost() +
            $this->getLabourCost() +
            $this->deliveryCost +
            $this->consumablesCost;
    }

    public function getTaxAmount()
    {
        return $this->getSubtotal() * ($this->taxRate / 100);
    }

    public function getGrandTotal()
    {
        return $this->getSubtotal() + $this->getTaxAmount();
    }

    // Summary: returns all data neatly
    public function getSummary()
    {
        return array(
            // Room info
            'room_length_m' => $this->length,
            'room_width_m'  => $this->width,
            'room_area_m2'  => round($this->getRoomArea(), 2),

            // Tile info
            'tile_length_mm' => $this->tileLength,
            'tile_width_mm'  => $this->tileWidth,
            'tile_area_m2'   => round($this->getTileArea(), 4),

            // Quantities
            'tiles_needed_raw'        => $this->getTilesNeededRaw(),
            'tiles_needed_with_waste' => $this->getTilesNeededWithWaste(),
            'boxes_needed'            => $this->getBoxesNeeded(),

            // Costs
            'material_cost'     => round($this->getMaterialCost(), 2),
            'labour_cost'       => round($this->getLabourCost(), 2),
            'delivery_cost'     => round($this->deliveryCost, 2),
            'consumables_cost'  => round($this->consumablesCost, 2),
            'subtotal'          => round($this->getSubtotal(), 2),

            // Tax + final
            'tax_rate_percent'  => $this->taxRate,
            'tax_amount'        => round($this->getTaxAmount(), 2),
            'grand_total'       => round($this->getGrandTotal(), 2),
        );
    }
}
