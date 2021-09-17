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
use Magento\Sales\Model\Order as SaleOrder;
use Mageplaza\Affiliate\Helper\Data;

/**
 * Class Order
 * @package Mageplaza\AffiliateGraphQl\Model\Resolver
 */
class Order implements ResolverInterface
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
     * @var SaleOrder
     */
    private $order;

    /**
     * @param GetCustomer $getCustomer
     * @param Data $data
     * @param SaleOrder $order
     */
    public function __construct(
        GetCustomer $getCustomer,
        Data $data,
        SaleOrder $order
    ) {
        $this->getCustomer = $getCustomer;
        $this->data        = $data;
        $this->order       = $order;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!$this->data->isEnabled()) {
            throw new GraphQlAuthorizationException(__('The Affiliate is disabled.'));
        }
        $order = $this->order->load($value['id']);

        return [
            'affiliate_key'                           => $order->getData('affiliate_key'),
            'affiliate_commission'                    => $order->getData('affiliate_commission'),
            'affiliate_discount_amount'               => $order->getData('affiliate_discount_amount'),
            'base_affiliate_discount_amount'          => $order->getData('base_affiliate_discount_amount'),
            'affiliate_shipping_commission'           => $order->getData('affiliate_shipping_commission'),
            'affiliate_earn_commission_invoice_after' => $order->getData('affiliate_earn_commission_invoice_after'),
            'affiliate_discount_invoiced'             => $order->getData('affiliate_discount_invoiced'),
            'base_affiliate_discount_invoiced'        => $order->getData('base_affiliate_discount_invoiced'),
            'affiliate_discount_refunded'             => $order->getData('affiliate_discount_refunded'),
            'base_affiliate_discount_refunded'        => $order->getData('base_affiliate_discount_refunded'),
            'affiliate_commission_invoiced'           => $order->getData('affiliate_commission_invoiced'),
            'affiliate_commission_refunded'           => $order->getData('affiliate_commission_refunded'),
            'affiliate_discount_shipping_amount'      => $order->getData('affiliate_discount_shipping_amount'),
            'base_affiliate_discount_shipping_amount' => $order->getData('base_affiliate_discount_shipping_amount'),
        ];
    }
}
