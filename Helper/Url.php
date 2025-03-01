<?php
/**
 * Copyright (c) 2020, Nosto Solutions Ltd
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
 * @copyright 2020 Nosto Solutions Ltd
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 *
 */

namespace Nosto\Tagging\Helper;

use Exception;
use Magento\Backend\Helper\Data as BackendDataHelper;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Url as UrlBuilder;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\Store;
use Nosto\Helper\UrlHelper;
use Nosto\Request\Http\HttpRequest;
use Nosto\Tagging\Helper\Data as NostoDataHelper;
use Nosto\Tagging\Logger\Logger as NostoLogger;
use Nosto\Tagging\Model\Product\Repository as ProductRepository;
use Nosto\Tagging\Model\Product\Url\Builder as NostoUrlBuilder;
use Zend_Uri_Exception;
use Zend_Uri_Http;

/**
 * Url helper class for common URL related tasks.
 */
class Url extends AbstractHelper
{
    public const URL_PATH_NOSTO_CONFIG = 'adminhtml/system_config/edit/section/nosto/';
    public const MAGENTO_URL_OPTION_STORE_ID = 'store';

    /**
     * Path to Magento's cart controller
     */
    public const MAGENTO_PATH_CART = 'checkout/cart';

    /**
     * The ___store parameter in Magento URLs
     */
    public const MAGENTO_URL_PARAMETER_STORE = '___store';

    /**
     * The array option key for scope in Magento's URLs
     */
    public const MAGENTO_URL_OPTION_SCOPE = '_scope';

    /**
     * The array option key for store to url in Magento's URLs
     */
    public const MAGENTO_URL_OPTION_SCOPE_TO_URL = '_scope_to_url';

    /**
     * The array option key for URL type in Magento's URLs
     */
    public const MAGENTO_URL_OPTION_LINK_TYPE = '_type';

    /**
     * Path to Nosto's restore cart controller
     */
    public const NOSTO_PATH_RESTORE_CART = 'nosto/frontend/cart';

    /**
     * The array option key for no session id in Magento's URLs.
     * The session id should be included into the URLs which are potentially
     * used during the same session, e.g. Oauth redirect URL. For example for
     * product URLs we cannot include the session id as the product URL should
     * be the same for all visitors and it will be saved to Nosto.
     */
    public const MAGENTO_URL_OPTION_NOSID = '_nosid';

    /**
     * The url type to be used for links.
     *
     * This is the only URL type that works correctly the URls when
     * "Add Store Code to Urls" setting is set to "Yes"
     *
     * UrlInterface::URL_TYPE_WEB
     * - returns an URL without rewrites and without store codes
     *
     * UrlInterface::URL_TYPE_LINK
     * - returns an URL with rewrites and with store codes in URL (if
     * setting "Add Store Code to Urls" set to yes)
     *
     * UrlInterface::URL_TYPE_DIRECT_LINK
     * - returns an URL with rewrites but without store codes
     *
     * @see UrlInterface::URL_TYPE_LINK
     *
     * @var string
     */
    public static string $urlType = UrlInterface::URL_TYPE_LINK;

    private CategoryCollectionFactory $categoryCollectionFactory;
    private UrlBuilder $urlBuilder;
    private Data $nostoDataHelper;
    private BackendDataHelper $backendDataHelper;
    private ProductRepository $productRepository;
    private NostoUrlBuilder $nostoUrlBuilder;
    private NostoLogger $logger;

    /**
     * Constructor.
     *
     * @param Context $context the context.
     * @param ProductRepository $productRepository
     * @param CategoryCollectionFactory $categoryCollectionFactory auto generated category collection factory.
     * @param Data $nostoDataHelper
     * @param UrlBuilder $urlBuilder frontend URL builder.
     * @param BackendDataHelper $backendDataHelper
     * @param NostoUrlBuilder $nostoUrlBuilder
     * @param NostoLogger $nostoLogger
     */
    public function __construct(
        Context $context,
        ProductRepository $productRepository,
        CategoryCollectionFactory $categoryCollectionFactory,
        NostoDataHelper $nostoDataHelper,
        UrlBuilder $urlBuilder,
        /** @noinspection PhpDeprecationInspection */
        BackendDataHelper $backendDataHelper,
        NostoUrlBuilder $nostoUrlBuilder,
        NostoLogger $nostoLogger
    ) {
        parent::__construct($context);

        $this->productRepository = $productRepository;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->urlBuilder = $urlBuilder;
        $this->nostoDataHelper = $nostoDataHelper;
        $this->backendDataHelper = $backendDataHelper;
        $this->nostoUrlBuilder = $nostoUrlBuilder;
        $this->logger = $nostoLogger;
    }

    /**
     * Returns the store domain
     *
     * @param Store $store
     * @return string
     */
    public function getActiveDomain(Store $store)
    {
        try {
            return UrlHelper::parseDomain($store->getBaseUrl());
        } catch (Exception $e) {
            $this->logger->exception($e);
            return '';
        }
    }

    /**
     * Gets the absolute URL to the current store view cart page.
     *
     * @param Store $store the store to get the url for.
     * @param string $currentUrl restore cart url
     * @return string cart url.
     * @throws NoSuchEntityException
     * @throws Zend_Uri_Exception
     */
    public function getUrlCart(Store $store, string $currentUrl)
    {
        $zendHttp = Zend_Uri_Http::fromString($currentUrl);
        $urlParameters = $zendHttp->getQueryAsArray();

        $defaultParams = $this->getUrlOptionsWithNoSid($store);
        $url = $store->getUrl(
            self::MAGENTO_PATH_CART,
            $defaultParams
        );

        if (!empty($urlParameters)) {
            foreach ($urlParameters as $key => $val) {
                $url = HttpRequest::replaceQueryParamInUrl(
                    $key,
                    $val,
                    $url
                );
            }
        }

        return $url;
    }

    /**
     * Returns the default options for fetching Magento urls with no session id
     *
     * @param Store $store
     * @return array
     */
    public function getUrlOptionsWithNoSid(Store $store)
    {
        return [
            self::MAGENTO_URL_OPTION_SCOPE_TO_URL => $this->nostoDataHelper->getStoreCodeToUrl($store),
            self::MAGENTO_URL_OPTION_NOSID => true,
            self::MAGENTO_URL_OPTION_LINK_TYPE => self::$urlType,
            self::MAGENTO_URL_OPTION_SCOPE => $store->getCode(),
        ];
    }

    /**
     * Gets the absolute URL to the Nosto configuration page
     *
     * @param Store $store the store to get the url for.
     *
     * @return string the url.
     */
    public function getAdminNostoConfigurationUrl(Store $store)
    {
        $params = [self::MAGENTO_URL_OPTION_STORE_ID => $store->getStoreId()];
        return $this->backendDataHelper->getUrl(self::URL_PATH_NOSTO_CONFIG, $params);
    }
}
