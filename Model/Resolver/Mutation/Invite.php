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

use Magento\Framework\DataObject;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\GraphQl\Model\Query\ContextInterface;
use Mageplaza\Affiliate\Helper\Data;
use Zend_Validate;
use Exception;

/**
 * Class Invite
 * @package Mageplaza\AffiliateGraphQl\Model\Resolver
 */
class Invite extends AbstractAffiliate
{
    const XML_PATH_REFER_EMAIL_TEMPLATE = 'affiliate/refer/account_sharing';
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

        $contacts = "";
        $referUrl = "";
        $subject = "";
        $content = "";

        if (isset($args['input']['contacts'])) {
            $contacts = $args['input']['contacts'];
        }

        if (!$contacts) {
            throw new GraphQlInputException(__('The contacts is required'));
        }

        if (isset($args['input']['refer_url'])) {
            $referUrl = $args['input']['refer_url'];
        }

        if (isset($args['input']['subject'])) {
            $subject = $args['input']['subject'];
        }

        if (isset($args['input']['content'])) {
            $content = $args['input']['content'];
        }

        $contacts = explode(",", $contacts);

        $customer = $this->getCustomer->execute($context);
        $affiliate = $this->data->getAffiliateAccount($customer->getId(), 'customer_id');

        if (!$referUrl || is_numeric(strpos($this->url->getBaseUrl(), $referUrl))) {
            $referUrl = $this->data->getSharingUrl() . $affiliate->getId();
        }

        if (!strpos($referUrl, $this->data->getSharingParam())) {
            $referUrl = $referUrl . $this->data->getSharingParam() . $affiliate->getId();
        }

        if (!$subject) {
            $subject = $this->data->getDefaultEmailSubject();
        }

        $store = $this->data->createObject(\Magento\Store\Model\StoreManagerInterface::class);

        $storeId = $storeId ?? 1;
        $content = $this->accountRepository->getEmailContent($content, $store->getStore($storeId)->getName(), $referUrl);

        $successEmails = $errorEmails = [];

        foreach ($contacts as $key => $email) {
            if (strpos($email, '<') !== false) {
                $name = substr($email, 0, strpos($email, '<'));
                $email = substr($email, strpos($email, '<') + 1);
            } else {
                $emailIdentify = explode('@', $email);
                $name = $emailIdentify[0];
            }

            $name = trim($name, '\'"');
            $email = trim(rtrim(trim($email), '>'));
            try {
                if (!Zend_Validate::is($email, 'EmailAddress')) {
                    continue;
                }

                $this->data->sendEmailTemplate(
                    new DataObject(['name' => $name, 'email' => $email, 'refer_url' => $referUrl]),
                    self::XML_PATH_REFER_EMAIL_TEMPLATE,
                    ['message' => $content, 'subject' => $subject],
                    Data::XML_PATH_EMAIL_SENDER,
                    $storeId
                );
                $successEmails[] = $email;
            } catch (Exception $e) {
                $errorEmails[] = $email;
            }
        }

        return Data::jsonEncode([
            "success" => $successEmails,
            "fail" => $errorEmails
        ]);
    }
}
