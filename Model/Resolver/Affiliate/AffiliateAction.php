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
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\GraphQl\Model\Query\ContextInterface;
use Mageplaza\Affiliate\Model\Api\AccountFactory as AccountAPIFactory;
use Mageplaza\Affiliate\Helper\Data;

/**
 * Class AffiliateAction
 * @package Mageplaza\AffiliateGraphQl\Model\Resolver\Affiliate
 */
class AffiliateAction implements ResolverInterface
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
     * @param GetCustomer $getCustomer
     * @param Data $data
     * @param AccountAPIFactory $accountAPIFactory
     */
    public function __construct(
        GetCustomer $getCustomer,
        Data $data,
        AccountAPIFactory $accountAPIFactory
    ) {
        $this->getCustomer       = $getCustomer;
        $this->data              = $data;
        $this->accountAPIFactory = $accountAPIFactory;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        /** @var ContextInterface $context */
        if (false === $context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(__('The current customer isn\'t authorized.'));
        }

        if (!$this->data->isEnabled()) {
            throw new GraphQlAuthorizationException(__('The Affiliate is disabled.'));
        }

        return [];
    }
}
