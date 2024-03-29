<?php

namespace Sunnysideup\Afterpay\Extensions;

use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\Forms\NumericField;
use SilverStripe\ORM\DataExtension;

/**
 * EcommerceRole provides customisations to the {@link Member}
 * class specifically for this ecommerce module.
 */
class AfterpayEcommerceConfigExtension extends DataExtension
{
    private static $db = [
        'ShowAfterpayOption' => 'Boolean',
        'AfterpayMinValue' => 'Int',
        'AfterpayMaxValue' => 'Int',
        'NoAfterpayMessage' => 'HTMLText',
    ];

    /**
     * @todo set hire purchase min value default
     */
    private static $defaults = [
        'ShowAfterpayOption' => true,
        'AfterpayMinValue' => 100,
        'AfterpayMaxValue' => 1000,
    ];

    public function UpdateCMSFields(FieldList $fields)
    {
        // Hire purchase fields
        $fields->addFieldToTab('Root.Payments', CheckboxField::create('PayBeforeCollect'));
        $fields->addFieldsToTab(
            'Root.Payments',
            [
                HeaderField::create(
                    'AfterpayHeader',
                    'After Pay'
                ),
                CheckboxField::create('ShowAfterpayOption', 'Enable afterpay on site'),
                NumericField::create('AfterpayMinValue', 'Lowest amount for afterpay')
                    ->setDescription(
                        'Only products equal to or above this amount can be bought using Afterpay.
                        This must be set above zero (0) to work.
                        Set to zero to turn off AfterPay.
                        '
                    ),
                NumericField::create('AfterpayMaxValue', 'Maxmimum amount for afterpay')
                    ->setDescription(
                        'Only products equal to or below this amount can be bought using Afterpay.
                        It is recommended to match this to the Hire Purchase start price.
                        Set to zero to turn off AfterPay.
                    '
                    ),
                HTMLEditorField::create('NoAfterpayMessage', 'Afterpay not available')
                    ->setDescription('Message to display when Afterpay is not available due to limits on the
                total cost of the items in the cart, please ensure it is in line with the minimum/maximum set value above.'),
            ]
        );
    }

    public function HasAfterpay()
    {
        return $this->owner->AfterpayMaxValue > 0;
    }

}
