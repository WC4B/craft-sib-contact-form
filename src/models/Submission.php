<?php
/**
 * craft-sib-contact plugin for Craft CMS 3.x
 *
 * A contact form integration for the Send in Blue API
 *
 * @link      https://github.com/WC4B/craft-sib-contact
 * @copyright Copyright (c) 2020 Joel Beer
 */

namespace wc4b\craftsibcontactform\models;

use wc4b\craftsibcontactform\Craftsibcontactform;

use Craft;
use craft\base\Model;

/**
 * Submission Model
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
class Submission extends Model
{
    // Public Properties
    // =========================================================================

  /**
     * @var string|null
     */
    public $fromName;

    /**
     * @var string|null
     */
    public $fromEmail;

    /**
     * @var string|null
     */
    public $subject;

    /**
     * @var string|string[]|null
     */
    public $message;

    /**
     * @var UploadedFile[]|null
     */
    public $attachment;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'fromName' => \Craft::t('craft-sib-contact-form', 'Your Name'),
            'fromEmail' => \Craft::t('craft-sib-contact-form', 'Your Email'),
            'message' => \Craft::t('craft-sib-contact-form', 'Message'),
            'subject' => \Craft::t('craft-sib-contact-form', 'Subject'),
        ];
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
            [['fromEmail', 'message'], 'required'],
            [['fromEmail'], 'email']
        ];
    }
}
