<?php

declare(strict_types=1);

namespace SoftCommerce\PlentyAmastyPromo\Plugin\PlentyOrderProfile\OrderImportService;

use Magento\Framework\Exception\LocalizedException;
use Magento\InventorySalesApi\Model\GetSkuFromOrderItemInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use SoftCommerce\PlentyOrderProfile\Model\OrderImportService\Processor\Shipment;

/**
 * Class ShipmentProcessorPlugin used to intercept shipment
 * import process in order to handle gift items.
 */
class ShipmentProcessorPlugin
{
    /**
     * @var GetSkuFromOrderItemInterface
     */
    private GetSkuFromOrderItemInterface $getSkuFromOrderItem;

    /**
     * @var array
     */
    private array $qtyRequest = [];

    /**
     * @param GetSkuFromOrderItemInterface $getSkuFromOrderItem
     */
    public function __construct(GetSkuFromOrderItemInterface $getSkuFromOrderItem)
    {
        $this->getSkuFromOrderItem = $getSkuFromOrderItem;
    }

    /**
     * @param Shipment $subject
     * @return void
     * @throws LocalizedException
     */
    public function beforeBuildRequest(Shipment $subject): void
    {
        $this->qtyRequest = [];
        foreach ($subject->getContext()->getSalesOrder()->getAllItems() as $orderItem) {
            if ($orderItem->getIsVirtual() || $orderItem->getLockedDoShip() || !$orderItem->canShip()) {
                continue;
            }

            $item = $orderItem->isDummy(true) ? $orderItem->getParentItem() : $orderItem;
            $sku = $this->getSkuFromOrderItem->execute($item);
            $qty = (float) $orderItem->getSimpleQtyToShip();
            $this->qtyRequest[$sku] = $qty + ($this->qtyRequest[$sku] ?? 0);
        }
    }

    /**
     * @param Shipment $subject
     * @param $result
     * @param OrderItemInterface $item
     * @return float
     */
    public function afterGetQtyToShip(Shipment $subject, $result, OrderItemInterface $item): float
    {
        $sku = $this->getSkuFromOrderItem->execute($item);
        return $this->qtyRequest[$sku] ?? $result;
    }
}
