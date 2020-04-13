<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace wc4b\sibcontactformintegration\events;

use wc4b\sibcontactformintegration\models\Signup;
use craft\mail\Message;
use yii\base\Event;

class SignupEvent extends Event
{
    /**
     * @var Signup The user signup.
     */
    public $signup;
}
