total_materials_cost      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
total_labour_cost         DECIMAL(10,2) NOT NULL DEFAULT 0.00,
total_delivery_cost       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
total_consumables_cost    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
total_subtotal_cost       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
total_tax_cost            DECIMAL(10,2) NOT NULL DEFAULT 0.00,
total_grand_total         DECIMAL(10,2) NOT NULL DEFAULT 0.00,

room_length_m             DECIMAL(10,2) NOT NULL DEFAULT 0.00,
room_width_m              DECIMAL(10,2) NOT NULL DEFAULT 0.00,
room_area_m2              DECIMAL(10,2) NOT NULL DEFAULT 0.00,

tile_length_mm            INT           NOT NULL DEFAULT 0,
tile_width_mm             INT           NOT NULL DEFAULT 0,
tile_area_m2              DECIMAL(10,4) NOT NULL DEFAULT 0.0000,

tiles_needed_raw          INT           NOT NULL DEFAULT 0,
tiles_needed_with_waste   INT           NOT NULL DEFAULT 0,
boxes_needed              INT           NOT NULL DEFAULT 0,

waste_percentage          DECIMAL(5,2)  NOT NULL DEFAULT 10.00,
tiles_per_box             INT           NOT NULL DEFAULT 4,

material_cost_per_m2      DECIMAL(10,2) NOT NULL DEFAULT 20.00,
labour_cost_per_m2        DECIMAL(10,2) NOT NULL DEFAULT 10.00,

tax_rate                  DECIMAL(5,2)  NOT NULL DEFAULT 20.00,

pallet_capacity_m2        DECIMAL(10,2) NOT NULL DEFAULT 100.00,
full_pallet_delivery      DECIMAL(10,2) NOT NULL DEFAULT 65.00,
half_pallet_delivery      DECIMAL(10,2) NOT NULL DEFAULT 55.00