<?php
/**
 * Copyright © Soft Commerce Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\PlentyAmastyPromo\Plugin\PlentyOrderProfile\OrderImportService;

use Magento\Framework\Exception\LocalizedException;
use SoftCommerce\PlentyOrderProfile\Model\OrderImportService\Generator\Shipment;

/**
 * Class ShipmentGeneratorPlugin used to intercept shipment
 * import generator in order to handle gift items.
 */
class ShipmentGeneratorPlugin
{
    /**
     * @var array
     */
    private array $qtyRequest = [];

    /**
     * @param Shipment $subject
     * @return void
     * @throws LocalizedException
     */
    public function beforeGenerate(Shipment $subject): void
    {
        $sku = $subject->getOrderItemSku();
        $qty = $subject->getOrderItemQty();
        $this->qtyRequest[$sku] = $qty + ($this->qtyRequest[$sku] ?? 0);
    }

    /**
     * @param Shipment $subject
     * @param $result
     * @return float
     * @throws LocalizedException
     */
    public function afterGetOrderItemQty(Shipment $subject, $result): float
    {
        return $this->qtyRequest[$subject->getOrderItemSku()] ?? $result;
    }
}
