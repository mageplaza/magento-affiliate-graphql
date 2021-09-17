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
use Magento\Framework\DataObject;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\UrlInterface;
use Magento\GraphQl\Model\Query\ContextInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mageplaza\Affiliate\Api\AccountRepositoryInterface;
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
     * @var StoreManagerInterface
     */
    private $storeManage;

    /**
     * @param GetCustomer $getCustomer
     * @param Data $data
     * @param AccountRepositoryInterface $accountRepository
     * @param UrlInterface $url
     * @param StoreManagerInterface $storeManage
     */
    public function __construct(
        GetCustomer $getCustomer,
        Data $data,
        AccountRepositoryInterface $accountRepository,
        UrlInterface $url,
        StoreManagerInterface $storeManage
    ) {
        $this->storeManage = $storeManage;
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
        /** @var ContextInterface $context */
        if (false === $context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(__('The current customer isn\'t authorized.'));
        }

        if (!$this->data->isEnabled()) {
            throw new GraphQlAuthorizationException(__('The Affiliate is disabled.'));
        }

        $contacts = $args['input']['contacts'];
        $subject  = $args['input']['subject'] ?? $this->data->getDefaultEmailSubject();

        $referUrl = "";
        $content  = "";

        if (isset($args['input']['refer_url'])) {
            $referUrl = $args['input']['refer_url'];
        }

        if (isset($args['input']['content'])) {
            $content = $args['input']['content'];
        }

        $contacts = explode(",", $contacts);

        $customer  = $this->getCustomer->execute($context);
        $affiliate = $this->data->getAffiliateAccount($customer->getId(), 'customer_id');

        if (!$referUrl || strpos($this->url->getBaseUrl(), $referUrl) === false) {
            $referUrl = $this->data->getSharingUrl() . $affiliate->getId();
        }

        if (strpos($referUrl, $this->data->getSharingParam()) === false) {
            $referUrl = $referUrl . $this->data->getSharingParam() . $affiliate->getId();
        }

        if (!$subject) {
            $subject = $this->data->getDefaultEmailSubject();
        }

        $content = $this->accountRepository->getEmailContent(
            $content,
            $this->storeManage->getStore($customer->getStoreId())->getName(),
            $referUrl
        );

        $successEmails = $errorEmails = [];

        foreach ($contacts as $email) {
            if (strpos($email, '<') === false) {
                $emailIdentify = explode('@', $email);
                $name          = $emailIdentify[0];
            } else {
                $name  = substr($email, 0, strpos($email, '<'));
                $email = substr($email, strpos($email, '<') + 1);
            }

            $name  = trim($name, '\'"');
            $email = trim(rtrim(trim($email), '>'));
            try {
                if (!Zend_Validate::is($email, 'EmailAddress')) {
                    continue;
                }

                $this->data->sendEmailTemplate(
                    new DataObject(['name' => $name, 'email' => $email, 'refer_url' => $referUrl]),
                    self::XML_PATH_REFER_EMAIL_TEMPLATE,
                    ['message' => $content, 'subject' => $subject],
                    Data::XML_PATH_EMAIL_SENDER
                );
                $successEmails[] = $email;
            } catch (Exception $e) {
                $errorEmails[] = $email;
            }
        }

        return Data::jsonEncode([
            "success" => $successEmails,
            "fail"    => $errorEmails
        ]);
    }
}
