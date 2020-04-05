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
use wc4bcraftsibcontactform\craftsibcontactform\models\Signup;
use wc4bcraftsibcontactform\craftsibcontactform\Craftsibcontactform as Plugin;

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

        // Create a new signup  
        $signup = new Signup();
        $signup->fromEmail = $request->getBodyParam('fromEmail');

        // TODO: Validate the Signup 

        // Check to see if contact exists if not add it
        if (!$this->contactExists($signup, $settings)) {
            return $this->createContact($signup, $settings);
        }

        // TODO: Handle contact existing but not in specified list

        // TODO: Handel no action i.e Contact exists and is the specified lists 
    }

     /**
     * Handle the creation of a new contact in Send In Blue
     *
     * @return Json
     */
    protected function createContact(Signup $signup, $settings)
    {
                
        // Build the SIB details
        $response = [];
        $response['success'] = true;

        $apiKey = $settings->sibApiKey;
        $lists = explode(",", $settings->sibLists);
        $contactConf = ['email' =>$signup->fromEmail,'listIds'=>$lists];
    
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
            
            // TODO Is there a better safer way to get this ? Can it be gottn from the API ?
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

    /**
     * Check to see if the contact exists within SIB
     *
     * @return Bool
     */
    protected function contactExists(Signup $signup, $settings)
    {
        // Set up defaults & values
        $response = false;
        $apiKey   = $settings->sibApiKey;
    
        // Set up SIB client
        // TODO: Move this set up in to the constructor ? remove the duplication in functions
        $config = \SendinBlue\Client\Configuration::getDefaultConfiguration()->setApiKey('api-key', $apiKey);
        $apiInstance = new \SendinBlue\Client\Api\ContactsApi(
            new \GuzzleHttp\Client(),
            $config
        );

        try {
            // If the contact exists return true
            $result = $apiInstance->getContactInfo($signup->fromEmail);
            if ($result->getEmail()) {
                // TODO: return the Contact object if it contains the contacts assosicated lists
                $response = true;
            }

        } catch (\SendinBlue\Client\ApiException $e) {
               
            // Decipher error message from the SendinBlue API Wrapper getting only the error message
            $exception = json_decode(trim(substr($e->getMessage(), strpos($e->getMessage(), 'response:') + 9)), true);
            // If the response is document_not_found the contact does not exist in SIB return false
            if ($exception['code'] = 'document_not_found') {
                $response = false;
            }
        }

        return $response;
    }
}
