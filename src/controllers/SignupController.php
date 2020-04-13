<?php
/**
 * sib-contact-form-integration plugin for Craft CMS 3.x
 *
 * A contact form integration for the Send in Blue API
 *
 * @link      https://github.com/WC4B/sib-contact-form-integration
 * @copyright Copyright (c) 2020 Joel Beer
 */

namespace wc4b\sibcontactformintegration\controllers;

use Craft;
use craft\web\Controller;
use wc4b\sibcontactformintegration\models\Signup;
use wc4b\sibcontactformintegration\Plugin;

/**
 * Signup Controller
 *
 * https://craftcms.com/docs/plugins/controllers
 *
 * @author    Joel Beer
 * @package   Craftsibcontactform
 * @since     1.0.0
 */
class SignupController extends Controller
{

    // Protected Properties
    // =========================================================================

    /**
     * @var    bool|array Allows anonymous access to this controller's actions.
     *         The actions must be in 'kebab-case'
     * @access protected
     */
    protected $allowAnonymous = ['index'];

    // Public Methods
    // =========================================================================

    /**
     * Handle a request going to our plugin's index action URL,
     * e.g.: actions/sib-contact-form-integration/signup
     *
     * @return mixed
     */
    public function actionIndex()
    {
        // Get the request
        $this->requirePostRequest();
        $request  = Craft::$app->getRequest();
        $plugin   = Plugin::getInstance();
        
        $response            = [];
        $response['success'] = true;
        // Create a new signup
        $signup = new Signup();
        $signup->email = $request->getBodyParam('email');
        
        if(!$signup->validate()){
            $response['success'] = false;
            $response['errors']  = $signup->getErrors();
            return $this->asJson($response);
        }       

        $response = $plugin->getSubscriber()->signup($signup);
        
        return $this->asJson($response);
    }
    
}
