<?php

declare(strict_types=1);

namespace SoftCommerce\PlentyAmastyPromo\Plugin\PlentyOrderProfile\OrderImportService;

use Magento\Framework\Exception\LocalizedException;
use Magento\InventorySalesApi\Model\GetSkuFromOrderItemInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use SoftCommerce\PlentyOrderProfile\Model\OrderImportService\Generator\Shipment;

/**
 * Class ShipmentGeneratorPlugin used to intercept shipment
 * import generator in order to handle gift items.
 */
class ShipmentGeneratorPlugin
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
    public function beforeGenerate(Shipment $subject): void
    {
        $this->qtyRequest = [];
        foreach ($subject->getContext()->getSalesOrder()->getAllItems() as $item) {
            if ($item->getIsVirtual() || $item->getLockedDoShip() || !$item->canShip()) {
                continue;
            }

            if ($item->isDummy(true)) {
                $item =  $item->getParentItem();
            }

            $sku = $this->getSkuFromOrderItem->execute($item);
            $qty = (float) $item->getSimpleQtyToShip();
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
