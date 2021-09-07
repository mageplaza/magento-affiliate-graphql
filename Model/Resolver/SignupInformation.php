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

use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Mageplaza\Affiliate\Helper\Data;

/**
 * Class SignupInformation
 * @package Mageplaza\AffiliateGraphQl\Model\Resolver
 */
class SignupInformation implements ResolverInterface
{
    /**
     * @var Data
     */
    private $data;

    /**
     * @param Data $data
     */
    public function __construct(
        Data $data
    ) {
        $this->data = $data;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!$this->data->isEnabled()) {
            throw new GraphQlAuthorizationException(__('The Affiliate is disabled.'));
        }

        return [
            'title' => $this->data->getTermsAndConditionsTitle(),
            'cms_block' => $this->data->loadCmsBlock($this->data->getTermsAndConditionsHtml()),
            'is_checked' => $this->data->isCheckedEmailNotification(),
            'checkbox_text' => $this->data->getTermsAndConditionsCheckboxText()
        ];
    }
}
