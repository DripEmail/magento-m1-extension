<?php

class Drip_Connect_Helper_Order extends Mage_Core_Helper_Abstract
{
    /**
     * prepare array of order data we use to send in drip for new orders
     *
     * @param Mage_Sales_Model_Order $order
     * @param Mage_Sales_Model_Order $config
     *
     * @return array
     */
    public function getOrderDataNew(Mage_Sales_Model_Order $order, Drip_Connect_Model_Configuration $config)
    {
        $orderTransform = new Drip_Connect_Model_Transformer_Order($order, $config);
        return $orderTransform->getOrderDataNew();
    }

    /**
     * prepare array of order data we use to send in drip for full/partly completed orders
     *
     * @param Mage_Sales_Model_Order $order
     * @param Drip_Connect_Model_Configuration $config
     *
     * @return array
     */
    public function getOrderDataCompleted(Mage_Sales_Model_Order $order, Drip_Connect_Model_Configuration $config)
    {
        $orderTransform = new Drip_Connect_Model_Transformer_Order($order, $config);
        return $orderTransform->getOrderDataCompleted();
    }

    /**
     * prepare array of order data we use to send in drip for canceled orders
     *
     * @param Mage_Sales_Model_Order $order
     * @param Drip_Connect_Model_Configuration $config
     *
     * @return array
     */
    public function getOrderDataCanceled(Mage_Sales_Model_Order $order, Drip_Connect_Model_Configuration $config)
    {
        $orderTransform = new Drip_Connect_Model_Transformer_Order($order, $config);
        return $orderTransform->getOrderDataCanceled();
    }

    /**
     * prepare array of order data we use to send in drip for full/partly refunded orders
     *
     * @param Mage_Sales_Model_Order $order
     * @param Drip_Connect_Model_Configuration $config
     * @param int $refundValue
     *
     * @return array
     */
    public function getOrderDataRefund(Mage_Sales_Model_Order $order, Drip_Connect_Model_Configuration $config, $refundValue)
    {
        $orderTransform = new Drip_Connect_Model_Transformer_Order($order, $config);
        return $orderTransform->getOrderDataRefund();
    }

    /**
     * prepare array of order data we use to send in drip for all other states
     *
     * @param Mage_Sales_Model_Order $order
     * @param Drip_Connect_Model_Configuration $config
     *
     * @return array
     */
    public function getOrderDataOther(Mage_Sales_Model_Order $order, Drip_Connect_Model_Configuration $config)
    {
        $orderTransform = new Drip_Connect_Model_Transformer_Order($order, $config);
        return $orderTransform->getOrderDataOther();
    }

    /**
     * check if given order can be sent to drip
     *
     * @param Mage_Sales_Model_Order $order
     * @param Drip_Connect_Model_Configuration $config
     *
     * @return bool
     */
    public function isCanBeSent(Mage_Sales_Model_Order $order, Drip_Connect_Model_Configuration $config)
    {
        $orderTransform = new Drip_Connect_Model_Transformer_Order($order, $config);
        return $orderTransform->isCanBeSent();
    }
}
