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
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\GraphQl\Model\Query\ContextInterface;
use Magento\Framework\GraphQl\Query\Resolver\Argument\SearchCriteria\Builder as SearchCriteriaBuilder;
use Mageplaza\Affiliate\Model\Api\AccountFactory as AccountAPIFactory;
use Mageplaza\Affiliate\Model\Campaign;
use Mageplaza\AffiliatePro\Api\Data\BannerSearchResultInterfaceFactory as BannerSearchResult;
use Mageplaza\AffiliateGraphQl\Model\Resolver\AbstractAffiliate;
use Mageplaza\Affiliate\Helper\Data;
use Mageplaza\AffiliatePro\Model\Banner\Status;
use Mageplaza\Affiliate\Model\Account;

/**
 * Class Banners
 * @package Mageplaza\AffiliateGraphQl\Model\Resolver\Affiliate
 */
class Banners extends AbstractAffiliate
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
     * @var BannerSearchResult
     */
    private $bannerFactory;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var AccountAPIFactory
     */
    private $accountAPIFactory;

    /**
     * @var Campaign
     */
    private $campaign;

    /**
     * @var Account
     */
    private $affiliate;

    /**
     * @param GetCustomer $getCustomer
     * @param Data $data
     * @param BannerSearchResult $bannerFactory
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param AccountAPIFactory $accountAPIFactory
     * @param Campaign $campaign
     */
    public function __construct(
        GetCustomer $getCustomer,
        Data $data,
        BannerSearchResult $bannerFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        AccountAPIFactory $accountAPIFactory,
        Campaign $campaign
    ) {
        $this->getCustomer           = $getCustomer;
        $this->data                  = $data;
        $this->bannerFactory         = $bannerFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->accountAPIFactory     = $accountAPIFactory;
        $this->campaign              = $campaign;
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

        $this->affiliate = $this->accountAPIFactory->create()->load($customer->getId(), 'customer_id');

        if (!$this->affiliate->getId()) {
            throw new GraphQlNoSuchEntityException(__('Requested entity doesn\'t exist'));
        }

        $searchCriteria   = $this->searchCriteriaBuilder->build('mp_affiliate_banner', $args);
        $bannerCollection = $this->bannerFactory->create();

        $searchCriteria->setCurrentPage($args['currentPage']);
        $searchCriteria->setPageSize($args['pageSize']);
        $searchResult = $this->data->processGetList($searchCriteria, $bannerCollection);

        $campaigns = $this->campaign->getCollection()
            ->getAvailableCampaign(
                $this->affiliate->getGroupId(),
                $customer->getWebsiteId()
            )
            ->getColumnValues('campaign_id');

        $searchResult->addFieldToFilter('campaign_id', ['in' => $campaigns])
            ->addFieldToFilter('status', Status::ENABLED);

        return $this->getResult($searchResult, $args);
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
            $link  = $item->getLink();
            $param = $this->data->getSharingParam() . $this->affiliate->getId();
            $item->setLink($link . $param);

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
}
