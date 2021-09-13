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

use Magento\CustomerGraphQl\Model\Customer\GetCustomer;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\GraphQl\Model\Query\ContextInterface;
use Magento\Quote\Model\Quote;
use Mageplaza\Affiliate\Helper\Data;

/**
 * Class Cart
 * @package Mageplaza\AffiliateGraphQl\Model\Resolver
 */
class Cart implements ResolverInterface
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
     * @var Quote
     */
    private $quote;

    /**
     * @param GetCustomer $getCustomer
     * @param Data $data
     * @param Quote $quote
     */
    public function __construct(
        GetCustomer $getCustomer,
        Data        $data,
        Quote       $quote
    )
    {
        $this->getCustomer = $getCustomer;
        $this->data = $data;
        $this->quote = $quote;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!$this->data->isEnabled()) {
            throw new GraphQlAuthorizationException(__('The Affiliate is disabled.'));
        }

        $quote = $this->quote->load($value['model']->getId());

        return [
            'affiliate_key' => $quote->getAffiliateKey(),
            'affiliate_discount_amount' => $quote->getAffiliateDiscountAmount(),
            'base_affiliate_discount_amount' => $quote->getBaseAffiliateDiscountAmount(),
            'affiliate_commission' => $quote->getAffiliateCommission(),
            'affiliate_shipping_commission' => $quote->getAffiliateShippingCommission(),
            'affiliate_discount_shipping_amount' => $quote->getAffiliateDiscountShippingAmount(),
            'base_affiliate_discount_shipping_amount' => $quote->getBaseAffiliateDiscountShippingAmount(),
        ];
    }
}
