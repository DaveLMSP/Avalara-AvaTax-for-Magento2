<?php
/**
 * @category    ClassyLlama
 * @copyright   Copyright (c) 2017 Classy Llama Studios, LLC
 */

namespace ClassyLlama\AvaTax\Model\ResourceModel;

class Invoice extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('avatax_sales_invoice', 'entity_id');
    }

    /**
     * Load AvaTax invoice record using parent invoice Id
     *
     * @param $invoiceId
     * @return string
     */
    public function loadByParentId($invoiceId){
        $table = $this->getMainTable();
        $where = $this->getConnection()->quoteInto("parent_id = ?", $invoiceId);
        $sql = $this->getConnection()->select()->from($table,array('entity_id'))->where($where);
        $record = $this->getConnection()->fetchOne($sql);
        return $record;
    }
}