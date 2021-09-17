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
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\GraphQl\Model\Query\ContextInterface;
use Magento\Framework\GraphQl\Query\Resolver\Argument\SearchCriteria\Builder as SearchCriteriaBuilder;
use Mageplaza\Affiliate\Api\Data\WithdrawSearchResultInterfaceFactory as WithdrawSearchResult;
use Mageplaza\AffiliateGraphQl\Model\Resolver\AbstractAffiliate;
use Mageplaza\Affiliate\Helper\Data;
use Magento\Directory\Model\Currency;

/**
 * Class Withdraw
 * @package Mageplaza\AffiliateGraphQl\Model\Resolver\Affiliate
 */
class Withdraw extends AbstractAffiliate
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
     * @var WithdrawSearchResult
     */
    private $withdrawFactory;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var Currency
     */
    protected $currency;

    /**
     * @param GetCustomer $getCustomer
     * @param Data $data
     * @param WithdrawSearchResult $withdrawFactory
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        GetCustomer $getCustomer,
        Data $data,
        WithdrawSearchResult $withdrawFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Currency $currency
    ) {
        $this->getCustomer           = $getCustomer;
        $this->data                  = $data;
        $this->withdrawFactory       = $withdrawFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;

        parent::__construct(
            $currency
        );
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $this->validate($args);
        /** @var ContextInterface $context */
        $context->getExtensionAttributes()->getIsCustomer();
        $customer = $this->getCustomer->execute($context);

        $searchCriteria     = $this->searchCriteriaBuilder->build('mp_affiliate_withdraw', $args);
        $withdrawCollection = $this->withdrawFactory->create()
            ->addFieldToFilter("customer_id", ["eq" => $customer->getId()]);

        $searchCriteria->setCurrentPage($args['currentPage']);
        $searchCriteria->setPageSize($args['pageSize']);
        $searchResult = $this->data->processGetList($searchCriteria, $withdrawCollection);

        $store = $context->getExtensionAttributes()->getStore();
        $this->createAdjustmentsArray($searchResult, $store);

        return $this->getResult($searchResult, $args);
    }

    /**
     * @param $searchResult
     * @param $store
     *
     * @return $this
     */
    public function createAdjustmentsArray(&$searchResult, $store)
    {
        foreach ($searchResult->getItems() as &$item) {
            $item['amount']          = $this->adjustmentsCurrency($item['amount'], $store);
            $item['fee']             = $this->adjustmentsCurrency($item['fee'], $store);
            $item['transfer_amount'] = $this->adjustmentsCurrency($item['transfer_amount'], $store);
        }

        return $this;
    }
}
