<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace wc4b\craftsibcontactform\events;

use wc4b\craftsibcontactform\models\Signup;
use craft\mail\Message;
use yii\base\Event;

class SignupEvent extends Event
{
    /**
     * @var Signup The user signup.
     */
    public $signup;
}
