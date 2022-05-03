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

namespace Nosto\Tagging\CustomerData;

use Magento\Customer\CustomerData\SectionSourceInterface;
use Magento\Customer\Helper\Session\CurrentCustomer;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Nosto\Model\Customer;
use Nosto\Tagging\Model\Customer\Customer as NostoCustomer;
use Nosto\Tagging\Model\Person\Tagging\Builder as NostoPersonBuilder;

class CustomerTagging extends HashedTagging implements SectionSourceInterface
{
    private CurrentCustomer $currentCustomer;
    private CookieManagerInterface $cookieManager;
    private NostoPersonBuilder $personBuilder;

    /**
     * CustomerTagging constructor.
     * @param CurrentCustomer $currentCustomer
     * @param CookieManagerInterface $cookieManager
     * @param NostoPersonBuilder $personBuilder
     */
    public function __construct(
        CurrentCustomer $currentCustomer,
        CookieManagerInterface $cookieManager,
        NostoPersonBuilder $personBuilder
    ) {
        $this->currentCustomer = $currentCustomer;
        $this->cookieManager = $cookieManager;
        $this->personBuilder = $personBuilder;
    }

    /**
     * @return array
     */
    public function getSectionData()
    {
        $data = [];
        if ($this->currentCustomer instanceof CurrentCustomer
            && $this->currentCustomer->getCustomerId()
        ) {
            /** @var Customer $customer */
            $customer = $this->personBuilder->fromSession($this->currentCustomer);
            if ($customer === null) {
                return [];
            }
            $nostoCustomerId = $this->cookieManager->getCookie(NostoCustomer::COOKIE_NAME);
            $data = [
                'first_name' => $customer->getFirstName(),
                'last_name' => $customer->getLastName(),
                'email' => $customer->getEmail(),
                'hcid' => $this->generateVisitorChecksum($nostoCustomerId),
                'marketing_permission' => $customer->getMarketingPermission(),
                'customer_reference' => $customer->getCustomerReference(),
                'customer_group' => $customer->getCustomerGroup(),
                'gender' => $customer->getGender(),
                'date_of_birth' => $customer->getDateOfBirth()
            ];
        }

        return $data;
    }
}
