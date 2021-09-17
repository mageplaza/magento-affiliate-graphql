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

use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\GraphQl\Model\Query\ContextInterface;

/**
 * Class CreateCoupon
 * @package Mageplaza\AffiliateGraphQl\Model\Resolver
 */
class CreateCoupon extends AbstractAffiliate
{
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

        $couponPrefix = $args['input']['coupon_prefix'];

        if (strlen($couponPrefix) < 6) {
            throw new GraphQlInputException(__('Please try with another coupon prefix.'));
        } else {
            $affiliateAccount   = $this->data->getAffiliateAccount($couponPrefix, 'code');
            $affiliateAccountId = $affiliateAccount->getId();

            $currentAffiliate   = $this->data->getAffiliateAccount(
                $context->getUserId(),
                'customer_id'
            );
            $currentAffiliateId = $currentAffiliate->getId();

            if ($currentAffiliateId) {
                if ($affiliateAccountId === null || $affiliateAccountId === $currentAffiliateId) {
                    $currentAffiliate->setData('code', $couponPrefix)->save();

                    return __('Successfully');
                } else {
                    return __('Coupon prefix is exists.');
                }
            }
        }

        return '';
    }
}
