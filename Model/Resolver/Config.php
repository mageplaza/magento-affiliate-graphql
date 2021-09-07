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
use Mageplaza\Affiliate\Helper\Data;

/**
 * Class Config
 * @package Mageplaza\AffiliateGraphQl\Model\Resolver
 */
class Config implements ResolverInterface
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
     * @param GetCustomer $getCustomer
     * @param Data $data
     */
    public function __construct(
        GetCustomer $getCustomer,
        Data        $data
    ) {
        $this->getCustomer = $getCustomer;
        $this->data = $data;
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

        $customer = $this->getCustomer->execute($context);
        $affiliate = $this->data->getAffiliateAccount($customer->getId(), 'customer_id');

        return [
            'email_notification' => $affiliate->getEmailNotification()
        ];
    }
}
