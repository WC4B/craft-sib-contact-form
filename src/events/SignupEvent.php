<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace wc4bcraftsibcontactform\craftsibcontactform\events;

use wc4bcraftsibcontactform\craftsibcontactform\models\Signup;
use craft\mail\Message;
use yii\base\Event;

class SignupEvent extends Event
{
    /**
     * @var Signup The user signup.
     */
    public $signup;
}
