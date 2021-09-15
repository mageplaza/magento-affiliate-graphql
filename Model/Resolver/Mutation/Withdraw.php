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
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\UrlInterface;
use Magento\GraphQl\Model\Query\ContextInterface;
use Mageplaza\Affiliate\Api\AccountRepositoryInterface;
use Mageplaza\Affiliate\Helper\Data;
use Mageplaza\Affiliate\Model\WithdrawFactory;
use Mageplaza\Affiliate\Model\Withdraw\Method;
use Exception;

/**
 * Class Withdraw
 * @package Mageplaza\AffiliateGraphQl\Model\Resolver
 */
class Withdraw extends AbstractAffiliate
{
    /**
     * @var WithdrawFactory
     */
    private $withdraw;

    /**
     * @var Method
     */
    private $paymentMethod;

    /**
     * @param GetCustomer $getCustomer
     * @param Data $data
     * @param AccountRepositoryInterface $accountRepository
     * @param UrlInterface $url
     * @param WithdrawFactory $withdraw
     * @param Method $paymentMethod
     */
    public function __construct(
        GetCustomer                $getCustomer,
        Data                       $data,
        AccountRepositoryInterface $accountRepository,
        UrlInterface               $url,
        WithdrawFactory            $withdraw,
        Method                     $paymentMethod
    )
    {
        $this->withdraw = $withdraw;
        $this->paymentMethod = $paymentMethod;
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

        $paymentMethod = $args['input']['payment_method'];
        $amount = $args['input']['amount'];

        if ($amount <= 0.001) {
            throw new InputException(__('Amount must great than zero'));
        }

        if ($paymentMethod) {
            $paymentMethods = $this->paymentMethod->getOptionHash();
            if (!isset($paymentMethods[$paymentMethod])) {
                throw new NoSuchEntityException(__('Payment method doesn\'t exist'));
            }

            if ($paymentMethod == 'paypal') {
                if (!isset($args['input']['paypal_email'])) {
                    throw new InputException(__('Paypal email required'));
                }

                if (!filter_var($args['input']['paypal_email'], FILTER_VALIDATE_EMAIL)) {
                    throw new InputException(__('Invalid paypal email address.'));
                }
            }
        }
        /** @var \Mageplaza\Affiliate\Model\Account $account */
        $customer = $this->getCustomer->execute($context);
        $account = $this->data->getAffiliateAccount($customer->getId(), 'customer_id');

        if (!$account->getId()) {
            throw new NoSuchEntityException(__('Affiliate account doesn\'t exist'));
        }

        $data = [
            'customer_id' => $account->getCustomerId(),
            'account_id' => $account->getId(),
            'amount' => $amount,
            'payment_method' => $paymentMethod,
            'withdraw_description' => $args['input']['withdraw_description'] ?? "",
            'offline_address' => $args['input']['offline_address'] ?? "",
            'banktranfer' => $args['input']['banktranfer'] ?? "",
            'paypal_email' => $args['input']['paypal_email'] ?? ""
        ];

        $withdraw = $this->withdraw->create();
        $withdraw->addData($data)->setAccount($account);

        $this->checkWithdrawAmount($withdraw);

        try {
            $withdraw->save();
        } catch (Exception $e) {
            throw new CouldNotSaveException((__('Could not save the withdraw: %1', $e->getMessage())));
        }

        return $withdraw->getId();
    }

    /**
     * @param $withdraw
     *
     * @return $this
     * @throws LocalizedException
     */
    public function checkWithdrawAmount($withdraw)
    {
        $minBalance = $this->data->getWithdrawMinimumBalance();
        if ($minBalance && $withdraw->getAccount()->getBalance() < $minBalance) {
            throw new GraphQlInputException(__('Your balance is not enough for request withdraw.'));
        }

        $min = $this->data->getWithdrawMinimum();
        if ($min && $withdraw->getAmount() < $min) {
            throw new GraphQlInputException(__(
                'The withdraw amount have to equal or greater than %1',
                $this->data->formatPrice($min)
            ));
        }

        $max = $this->data->getWithdrawMaximum();
        if ($max && $withdraw->getAmount() > $max) {
            throw new GraphQlInputException(__(
                'The withdraw amount have to equal or less than %1',
                $this->data->formatPrice($max)
            ));
        }

        return $this;
    }
}
