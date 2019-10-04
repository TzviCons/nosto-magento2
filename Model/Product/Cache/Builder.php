<?php
/**
 * Copyright (c) 2019, Nosto Solutions Ltd
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice,
 * this list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 * this list of conditions and the following disclaimer in the documentation
 * and/or other materials provided with the distribution.
 *
 * 3. Neither the name of the copyright holder nor the names of its contributors
 * may be used to endorse or promote products derived from this software without
 * specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR
 * ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
 * ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @author Nosto Solutions Ltd <contact@nosto.com>
 * @copyright 2019 Nosto Solutions Ltd
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 *
 */

namespace Nosto\Tagging\Model\Product\Cache;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Api\Data\StoreInterface;
use Nosto\Tagging\Model\Product\Builder as NostoProductBuilder;
use Nosto\Tagging\Model\Product\BuilderTrait;
use Nosto\Tagging\Model\Product\Cache;
use Nosto\Tagging\Model\Product\CacheFactory as NostoCacheFactory;

class Builder
{
    use BuilderTrait {
        BuilderTrait::__construct as builderTraitConstruct; // @codingStandardsIgnoreLine
    }

    /** @var NostoCacheFactory  */
    private $NostoCacheFactory;

    /** @var NostoProductBuilder */
    private $nostoProductBuilder;

    /** @var TimezoneInterface */
    private $magentoTimeZone;

    /**
     * Builder constructor.
     * @param NostoCacheFactory $NostoCacheFactory
     * @param TimezoneInterface $magentoTimeZone
     */
    public function __construct(
        NostoCacheFactory $NostoCacheFactory,
        TimezoneInterface $magentoTimeZone
    ) {
        $this->NostoCacheFactory = $NostoCacheFactory;
        $this->magentoTimeZone = $magentoTimeZone;
    }

    /**
     * @param ProductInterface $product
     * @param StoreInterface $store
     * @return \Nosto\Tagging\Model\Product\Cache
     */
    public function build(
        ProductInterface $product,
        StoreInterface $store
    ) {
        $productIndex = $this->NostoCacheFactory->create();
        $productIndex->setProductId($product->getId());
        $productIndex->setCreatedAt($this->magentoTimeZone->date());
        $productIndex->setInSync(false);
        $productIndex->setIsDirty(true);
        $productIndex->setUpdatedAt($this->magentoTimeZone->date());
        $productIndex->setStore($store);
        return $productIndex;
    }
}
