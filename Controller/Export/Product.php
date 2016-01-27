<?php

namespace Nosto\Tagging\Controller\Export;

use Magento\Catalog\Model\Product\Visibility as ProductVisibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\App\Action\Context;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Nosto\Tagging\Helper\Account as AccountHelper;
use Nosto\Tagging\Model\Product\Builder as ProductBuilder;
use NostoExportCollectionProduct;

/**
 * Product export controller used to export product history to Nosto in order to
 * bootstrap the recommendations during initial account creation.
 * This controller will be called by Nosto when a new account has been created
 * from the Magento backend. The controller is public, but the information is
 * encrypted with AES, and only Nosto can decrypt it.
 */
class Product extends Base
{

    private $_productCollectionFactory;
    private $_productVisibility;
    private $_productBuilder;

    /**
     * Constructor.
     *
     * @param Context                  $context
     * @param ProductCollectionFactory $productCollectionFactory
     * @param ProductVisibility        $productVisibility
     * @param StoreManagerInterface    $storeManager
     * @param AccountHelper            $accountHelper
     * @param ProductBuilder           $productBuilder
     */
    public function __construct(
        Context $context,
        ProductCollectionFactory $productCollectionFactory,
        ProductVisibility $productVisibility,
        StoreManagerInterface $storeManager,
        AccountHelper $accountHelper,
        ProductBuilder $productBuilder
    ) {
        parent::__construct($context, $storeManager, $accountHelper);

        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_productVisibility = $productVisibility;
        $this->_productBuilder = $productBuilder;
    }

    /**
     * @inheritdoc
     */
    protected function getCollection(Store $store)
    {
        /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $collection */
        $collection = $this->_productCollectionFactory->create();
        $collection->setVisibility($this->_productVisibility->getVisibleInSiteIds());
        $collection->addAttributeToFilter('status', ['eq' => '1']);
        $collection->addStoreFilter($store->getId());
        return $collection;
    }

    /**
     * @inheritdoc
     */
    protected function buildExportCollection($collection, Store $store)
    {
        /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $collection */
        $exportCollection = new NostoExportCollectionProduct();
        foreach ($collection->getItems() as $product) {
            /** @var \Magento\Catalog\Model\Product $product */
            $nostoProduct = $this->_productBuilder->build($product, $store);
            $exportCollection[] = $nostoProduct;
        }
        return $exportCollection;
    }
}