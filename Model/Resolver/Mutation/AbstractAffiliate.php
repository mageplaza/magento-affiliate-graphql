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

namespace Mageplaza\AffiliateGraphQl\Model\Resolver\Mutation;

use Magento\CustomerGraphQl\Model\Customer\GetCustomer;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\UrlInterface;
use Mageplaza\Affiliate\Helper\Data;
use Mageplaza\Affiliate\Api\AccountRepositoryInterface;

/**
 * Class AbstractAffiliate
 * @package Mageplaza\AffiliateGraphQl\Model\Resolver
 */
abstract class AbstractAffiliate implements ResolverInterface
{
    /**
     * @var GetCustomer
     */
    protected $getCustomer;

    /**
     * @var Data
     */
    protected $data;

    /**
     * @var AccountRepositoryInterface
     */
    protected $accountRepository;

    /**
     * @var UrlInterface
     */
    protected $url;

    /**
     * @param GetCustomer $getCustomer
     * @param Data $data
     * @param AccountRepositoryInterface $accountRepository
     * @param UrlInterface $url
     */
    public function __construct(
        GetCustomer $getCustomer,
        Data $data,
        AccountRepositoryInterface $accountRepository,
        UrlInterface $url
    ) {
        $this->getCustomer       = $getCustomer;
        $this->data              = $data;
        $this->accountRepository = $accountRepository;
        $this->url               = $url;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        return [];
    }
}
