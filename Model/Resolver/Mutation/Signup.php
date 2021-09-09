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
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\GraphQl\Model\Query\ContextInterface;
use Mageplaza\Affiliate\Helper\Data;
use Mageplaza\Affiliate\Model\Account\Status;
use Exception;

/**
 * Class Signup
 * @package Mageplaza\AffiliateGraphQl\Model\Resolver
 */
class Signup extends AbstractAffiliate
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

        $email = "";

        if (isset($args['input']['email'])) {
            $email = $args['input']['email'];
        }

        $customer = $this->getCustomer->execute($context);
        $affiliate = $this->data->getAffiliateAccount($customer->getId(), 'customer_id');

        if ($affiliate->getId() && !$affiliate->isActive()) {
            return Data::jsonEncode(["status" => Status::INACTIVE]);
        }

        $data = [];
        $data['customer_id'] = $context->getUserId();
        $signUpConfig = $this->data->getAffiliateAccountSignUp();
        $data['group_id'] = $signUpConfig['default_group'];

        if ($email) {
            /** @var \Mageplaza\Affiliate\Model\Account $parent */
            $parent = $this->data->getAffiliateByEmailOrCode(strtolower(trim($email)));
            $data['parent'] = $parent->getId();
            $data['parent_email'] = $parent->getCustomer()->getEmail();
        }
        $data['status'] = $signUpConfig['admin_approved'] ? Status::NEED_APPROVED : Status::ACTIVE;
        $data['email_notification'] = $signUpConfig['default_email_notification'];

        try {
            $affiliate->addData($data)->save();
            if ($affiliate->getStatus() == Status::NEED_APPROVED) {
                return Data::jsonEncode(["status" => Status::NEED_APPROVED]);
            }

            return Data::jsonEncode(["status" => Status::ACTIVE, "affiliate_id" => $affiliate->getId()]);

        } catch (Exception $e) {
            return Data::jsonEncode(["message" => $e->getMessage()]);
        }
    }
}
