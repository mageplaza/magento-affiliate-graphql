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

namespace Mageplaza\AffiliateGraphQl\Model\Resolver;

use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Directory\Model\Currency;

/**
 * Class AbstractAttributes
 * @package Mageplaza\AffiliateGraphQl\Model\Resolver
 */
abstract class AbstractAffiliate implements ResolverInterface
{
    /**
     * @var Currency
     */
    protected $currency;

    /**
     * @param Currency $currency
     */
    public function __construct(
        Currency $currency
    ) {
        $this->currency = $currency;
    }

    /**
     * @param array $args
     *
     * @throws GraphQlInputException
     */
    public function validate($args)
    {
        if (isset($args['currentPage']) && $args['currentPage'] < 1) {
            throw new GraphQlInputException(__('currentPage value must be greater than 0.'));
        }

        if (isset($args['pageSize']) && $args['pageSize'] < 1) {
            throw new GraphQlInputException(__('pageSize value must be greater than 0.'));
        }
    }

    /**
     * @param SearchResultsInterface $searchResult
     * @param array $args
     *
     * @return array
     * @throws GraphQlInputException
     */
    public function getResult($searchResult, $args)
    {
        $items = [];
        foreach ($searchResult->getItems() as $item) {
            $items[] = $item->getData();
        }

        return [
            'total_count' => $searchResult->getTotalCount(),
            'items'       => $items,
            'page_info'   => $this->getPageInfo($searchResult, $args)
        ];
    }

    /**
     * @param SearchResultsInterface $searchResult
     * @param array $args
     *
     * @return array
     * @throws GraphQlInputException
     */
    private function getPageInfo($searchResult, $args)
    {
        $totalPages  = ceil($searchResult->getTotalCount() / $args['pageSize']);
        $currentPage = $args['currentPage'];

        if ($currentPage > $totalPages && $searchResult->getTotalCount() > 0) {
            throw new GraphQlInputException(
                __(
                    'currentPage value %1 specified is greater than the %2 page(s) available.',
                    [$currentPage, $totalPages]
                )
            );
        }

        return [
            'pageSize'        => $args['pageSize'],
            'currentPage'     => $currentPage,
            'hasNextPage'     => $currentPage < $totalPages,
            'hasPreviousPage' => $currentPage > 1,
            'startPage'       => 1,
            'endPage'         => $totalPages,
        ];
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
