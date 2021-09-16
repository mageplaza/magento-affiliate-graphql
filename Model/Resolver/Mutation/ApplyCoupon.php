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
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\UrlInterface;
use Magento\GraphQl\Model\Query\ContextInterface;
use Mageplaza\Affiliate\Api\AccountRepositoryInterface;
use Mageplaza\Affiliate\Api\CouponManagementInterface;
use Mageplaza\Affiliate\Api\GuestCouponManagementInterface;
use Mageplaza\Affiliate\Helper\Data;

/**
 * Class ApplyCoupon
 * @package Mageplaza\AffiliateGraphQl\Model\Resolver
 */
class ApplyCoupon extends AbstractAffiliate
{
    /**
     * @var CouponManagementInterface
     */
    private $couponManagement;

    /**
     * @var GuestCouponManagementInterface
     */
    private $guestCouponManagement;

    /**
     * @param GetCustomer $getCustomer
     * @param Data $data
     * @param AccountRepositoryInterface $accountRepository
     * @param UrlInterface $url
     * @param CouponManagementInterface $couponManagement
     * @param GuestCouponManagementInterface $guestCouponManagement
     */
    public function __construct(
        GetCustomer                    $getCustomer,
        Data                           $data,
        AccountRepositoryInterface     $accountRepository,
        UrlInterface                   $url,
        CouponManagementInterface      $couponManagement,
        GuestCouponManagementInterface $guestCouponManagement
    )
    {
        $this->couponManagement = $couponManagement;
        $this->guestCouponManagement = $guestCouponManagement;

        parent::__construct(
            $getCustomer,
            $data,
            $accountRepository,
            $url
        );
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!$this->data->isEnabled()) {
            throw new GraphQlAuthorizationException(__('The Affiliate is disabled.'));
        }

        $cartId = $args['input']['cart_id'];
        $coupon = $args['input']['coupon'];

        /** @var ContextInterface $context */
        if ($context->getExtensionAttributes()->getIsCustomer()) {
            return $this->applyCoupon($this->couponManagement, $cartId, $coupon);
        } else {
            return $this->applyCoupon($this->guestCouponManagement, $cartId, $coupon);
        }
    }

    /**
     * @param $interface
     * @param $cartId
     * @param $coupon
     * @return bool
     * @throws GraphQlNoSuchEntityException
     */
    public function applyCoupon($interface, $cartId, $coupon)
    {
        try {
            $interface->set($cartId, $coupon);
        } catch (NoSuchEntityException $e) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()));
        }

        return true;
    }
}
