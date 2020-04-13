<?php
/**
 * craft-sib-contact-form plugin for Craft CMS 3.x
 *
 * A contact form integration for the Send in Blue API
 *
 * @link      https://github.com/WC4B/craft-sib-contact-form
 * @copyright Copyright (c) 2020 Joel Beer
 */

namespace wc4b\craftsibcontactform\models;

use wc4b\craftsibcontactform\Craftsibcontactform;

use Craft;
use craft\base\Model;

/**
 * Settings Model
 *
 * Models are containers for data. Just about every time information is passed
 * between services, controllers, and templates in Craft, it’s passed via a model.
 *
 * https://craftcms.com/docs/plugins/models
 *
 * @author    Joel Beer
 * @package   Craftsibcontactform
 * @since     1.0.0
 */
class Settings extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * @var string|string[]|null
     */
    public $toEmail;

    /**
     * @var string|string[]|null
     */
    public $sibApiKey;

    /**
     * @var string|string[]|null
     */
    public $sibLists;

    /**
     * @var string|null
     */
    public $prependSender;

    /**
     * @var string|null
     */
    public $prependSubject;

    /**
     * @var bool
     */
    public $allowAttachments = false;

    /**
     * @var string|null
     */
    public $successFlashMessage;
   
    // Public Methods
    // =========================================================================

     /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if ($this->prependSender === null) {
            $this->prependSender = \Craft::t('craft-sib-contact-form', 'On behalf of');
        }

        if ($this->prependSubject === null) {
            $this->prependSubject = \Craft::t('craft-sib-contact-form', 'New message from {siteName}', [
                'siteName' => \Craft::$app->getSites()->getCurrentSite()->name
            ]);
        }

        if ($this->successFlashMessage === null) {
            $this->successFlashMessage = \Craft::t('craft-sib-contact-form', 'Your message has been sent.');
        }
    }


    /**
     * Returns the validation rules for attributes.
     *
     * Validation rules are used by [[validate()]] to check if attribute values are valid.
     * Child classes may override this method to declare different validation rules.
     *
     * More info: http://www.yiiframework.com/doc-2.0/guide-input-validation.html
     *
     * @return array
     */
    public function rules()
    {
        return [
            [['toEmail', 'successFlashMessage','sibApiKey'], 'required'],
            [['toEmail', 'prependSender', 'prependSubject', 'successFlashMessage'], 'string'],
        ];
    }
}
