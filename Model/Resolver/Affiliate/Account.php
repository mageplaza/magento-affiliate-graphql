<?php
/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Mageplaza.com license that is
 * available through the world-wide-web at this URL:
 * https://www.mageplaza.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Mageplaza
 * @package     Mageplaza_AffiliateGraphQl
 * @copyright   Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */
declare(strict_types=1);

namespace Mageplaza\AffiliateGraphQl\Model\Resolver\Affiliate;

use Magento\CustomerGraphQl\Model\Customer\GetCustomer;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\GraphQl\Model\Query\ContextInterface;
use Mageplaza\Affiliate\Model\Api\AccountFactory as AccountAPIFactory;
use Mageplaza\Affiliate\Helper\Data;
use Magento\Directory\Model\Currency;

/**
 * Class Account
 * @package Mageplaza\AffiliateGraphQl\Model\Resolver\Affiliate
 */
class Account implements ResolverInterface
{
    /**
     * @var GetCustomer
     */
    private $getCustomer;

    /**
     * @var Data
     */
    private $data;

    /**
     * @var AccountAPIFactory
     */
    private $accountAPIFactory;

    /**
     * @var Currency
     */
    private $currency;

    /**
     * @param GetCustomer $getCustomer
     * @param Data $data
     * @param AccountAPIFactory $accountAPIFactory
     */
    public function __construct(
        GetCustomer $getCustomer,
        Data $data,
        AccountAPIFactory $accountAPIFactory,
        Currency $currency
    ) {
        $this->getCustomer       = $getCustomer;
        $this->data              = $data;
        $this->accountAPIFactory = $accountAPIFactory;
        $this->currency          = $currency;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        /** @var ContextInterface $context */
        $context->getExtensionAttributes()->getIsCustomer();
        $customer = $this->getCustomer->execute($context);

        $account = $this->accountAPIFactory->create()->load($customer->getId(), 'customer_id');

        if (!$account->getId()) {
            throw new GraphQlNoSuchEntityException(__('Requested entity doesn\'t exist'));
        }

        $store = $context->getExtensionAttributes()->getStore();
        $this->changePriceData($account, $store);

        return $account;
    }

    /**
     * @param $account
     * @param $store
     */
    public function changePriceData(&$account, $store)
    {
        $data = [
            'balance'          => $this->adjustmentsCurrency($account->getBalance(), $store),
            'holding_balance'  => $this->adjustmentsCurrency($account->getHoldingBalance(), $store),
            'total_commission' => $this->adjustmentsCurrency($account->getTotalCommission(), $store),
            'total_paid'       => $this->adjustmentsCurrency($account->getTotalPaid(), $store),
        ];

        $account->addData($data);
    }

    /**
     * @param $value
     * @param $store
     *
     * @return array
     */
    public function adjustmentsCurrency($value, $store)
    {
        $baseCurrency    = $this->currency->getConfigBaseCurrencies()[0];
        $convertCurrency = $store->getCurrentCurrencyCode();

        return [
            'value'    => $this->convertCurrency($baseCurrency, $convertCurrency, $value),
            'currency' => $convertCurrency
        ];
    }

    /**
     * @param $from
     * @param $to
     * @param $amount
     *
     * @return float|int
     */
    public function convertCurrency($from, $to, $amount)
    {
        if ($from == $to) {
            return $amount;
        }
        $this->currency->load($from);
        $rate = $this->currency->getAnyRate($to);

        return $rate * $amount;
    }
}
