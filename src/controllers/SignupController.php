<?php
/**
 * craft-sib-contact-form plugin for Craft CMS 3.x
 *
 * A contact form integration for the Send in Blue API
 *
 * @link      https://github.com/WC4B/craft-sib-contact-form
 * @copyright Copyright (c) 2020 Joel Beer
 */

namespace wc4bcraftsibcontactform\craftsibcontactform\controllers;

use wc4bcraftsibcontactform\craftsibcontactform\Craftsibcontactform;

use Craft;
use craft\web\Controller;
use wc4bcraftsibcontactform\craftsibcontactform\models\Submission;
use wc4bcraftsibcontactform\craftsibcontactform\Craftsibcontactform as Plugin;

/**
 * Signup Controller
 *
 * Generally speaking, controllers are the middlemen between the front end of
 * the CP/website and your plugin’s services. They contain action methods which
 * handle individual tasks.
 *
 * A common pattern used throughout Craft involves a controller action gathering
 * post data, saving it on a model, passing the model off to a service, and then
 * responding to the request appropriately depending on the service method’s response.
 *
 * Action methods begin with the prefix “action”, followed by a description of what
 * the method does (for example, actionSaveIngredient()).
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
    protected $allowAnonymous = ['index', 'do-something'];

    // Public Methods
    // =========================================================================

    /**
     * Handle a request going to our plugin's index action URL,
     * e.g.: actions/craft-sib-contact-form/signup
     *
     * @return mixed
     */
    public function actionIndex()
    {
        // Get the request
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();
        $settings = Plugin::getInstance()->getSettings();

        $submission = new Submission();
        $submission->fromEmail = $request->getBodyParam('fromEmail');

        if (!$this->contactExists($submission, $settings)) {
            return $this->sendToSIB($submission, $settings);
        }
    }

    public function sendToSIB(Submission $submission, $settings)
    {
                
        // Build the SIB details
        $response = [];
        $response['success'] = true;

        $apiKey = $settings->sibApiKey;
        $lists = explode(",", $settings->sibLists);
        $contactConf = ['email' =>$submission->fromEmail,'listIds'=>$lists];
    
        // Set up SIB client
        $config = \SendinBlue\Client\Configuration::getDefaultConfiguration()->setApiKey('api-key', $apiKey);
        $apiInstance = new \SendinBlue\Client\Api\ContactsApi(
            new \GuzzleHttp\Client(),
            $config
        );

        // Create SIB contact object
        $createContact = new \SendinBlue\Client\Model\CreateContact($contactConf);
        try {
            // Send the contact to SIB
            $result = $apiInstance->createContact($createContact);
        } catch (\SendinBlue\Client\ApiException $e) {
            
            // Decipher error message from the SendinBlue API Wrapper getting only the error message
            $exception = json_decode(trim(substr($e->getMessage(), strpos($e->getMessage(), 'response:') + 9)), true);
            if ($exception['code'] = 'duplicate_parameter') {
                $response['success'] = false;
                $response['errors'] = ["signupError "=> $exception['code']];
                return $this->asJson(['errors' => ["signupError "=> $exception['code']]]);
            }
        }

        return $this->asJson($response);
    }

    public function contactExists(Submission $submission, $settings)
    {
        $response = false;
        $apiKey   = $settings->sibApiKey;
    
        // Set up SIB client
        $config = \SendinBlue\Client\Configuration::getDefaultConfiguration()->setApiKey('api-key', $apiKey);
        $apiInstance = new \SendinBlue\Client\Api\ContactsApi(
            new \GuzzleHttp\Client(),
            $config
        );

        try {
            // if the contact exists return true
            $result = $apiInstance->getContactInfo($submission->fromEmail);
            if ($result->getEmail()) {
                $response = true;
            }
        } catch (\SendinBlue\Client\ApiException $e) {
               
            // Check to see if the contact exists
            $exception = json_decode(trim(substr($e->getMessage(), strpos($e->getMessage(), 'response:') + 9)), true);
            if ($exception['code'] = 'document_not_found') {
                $response = false;
            }
        }

        return $response;
    }
}
