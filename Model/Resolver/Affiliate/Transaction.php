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
use Mageplaza\Affiliate\Api\Data\TransactionSearchResultInterfaceFactory as TransactionSearchResult;
use Mageplaza\AffiliateGraphQl\Model\Resolver\AbstractAffiliate;
use Mageplaza\Affiliate\Helper\Data;
use Magento\Directory\Model\Currency;

/**
 * Class Transaction
 * @package Mageplaza\AffiliateGraphQl\Model\Resolver\Affiliate
 */
class Transaction extends AbstractAffiliate
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
     * @var TransactionSearchResult
     */
    private $transactionFactory;

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
     * @param TransactionSearchResult $transactionFactory
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        GetCustomer $getCustomer,
        Data $data,
        TransactionSearchResult $transactionFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Currency $currency
    ) {
        $this->getCustomer           = $getCustomer;
        $this->data                  = $data;
        $this->transactionFactory    = $transactionFactory;
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

        $searchCriteria        = $this->searchCriteriaBuilder->build('mp_affiliate_transaction', $args);
        $transactionCollection = $this->transactionFactory->create()
            ->addFieldToFilter("customer_id", ["eq" => $customer->getId()]);

        $searchCriteria->setCurrentPage($args['currentPage']);
        $searchCriteria->setPageSize($args['pageSize']);
        $searchResult = $this->data->processGetList($searchCriteria, $transactionCollection);

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
            $item['amount_used']     = $this->adjustmentsCurrency($item['amount_used'], $store);
            $item['current_balance'] = $this->adjustmentsCurrency($item['current_balance'], $store);
        }

        return $this;
    }
}
