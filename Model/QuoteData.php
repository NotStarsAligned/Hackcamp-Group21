<?php
// Model/QuoteData.php

class QuoteData
{
    // Properties matching the 'quotes' table columns
    protected $_id, $_customer_id, $_site_address_id, $_created_by_user_id;
    protected $_reference_code, $_status, $_valid_until, $_currency_code;
    protected $_total_materials_cost, $_total_labour_cost, $_total_delivery_cost;
    protected $_total_consumables_cost, $_total_tax, $_grand_total;
    protected $_notes_internal, $_notes_customer, $_created_at, $_updated_at;

    public function __construct($dbRow)
    {
        $this->_id = $dbRow['id'] ?? null;
        $this->_customer_id = $dbRow['customer_id'] ?? null;
        $this->_site_address_id = $dbRow['site_address_id'] ?? null;
        $this->_created_by_user_id = $dbRow['created_by_user_id'] ?? null;
        $this->_reference_code = $dbRow['reference_code'] ?? null;
        $this->_status = $dbRow['status'] ?? null;
        $this->_valid_until = $dbRow['valid_until'] ?? null;
        $this->_currency_code = $dbRow['currency_code'] ?? null;
        $this->_total_materials_cost = $dbRow['total_materials_cost'] ?? 0.00;
        $this->_total_labour_cost = $dbRow['total_labour_cost'] ?? 0.00;
        $this->_total_delivery_cost = $dbRow['total_delivery_cost'] ?? 0.00;
        $this->_total_consumables_cost = $dbRow['total_consumables_cost'] ?? 0.00;
        $this->_total_tax = $dbRow['total_tax'] ?? 0.00;
        $this->_grand_total = $dbRow['grand_total'] ?? 0.00;
        $this->_notes_internal = $dbRow['notes_internal'] ?? null;
        $this->_notes_customer = $dbRow['notes_customer'] ?? null;
        $this->_created_at = $dbRow['created_at'] ?? null;
        $this->_updated_at = $dbRow['updated_at'] ?? null;
    }

    // Getters for essential properties
    public function getId() { return $this->_id; }
    public function getCustomerId() { return $this->_customer_id; }
    public function getReference() { return $this->_reference_code; }
    public function getStatus() { return $this->_status; }
    public function getGrandTotal() { return $this->_grand_total; }
    public function getCreatedAt() { return $this->_created_at; }

    // Add other getters as needed for accessing remaining properties...
}