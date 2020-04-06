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
    protected $apiInstance;
    protected $pluginSettings;

    
    public function beforeAction($action)
    {
        $this->pluginSettings = Plugin::getInstance()->getSettings();
        $apiKey   =  $this->pluginSettings->sibApiKey;
        $config = \SendinBlue\Client\Configuration::getDefaultConfiguration()->setApiKey('api-key', $apiKey);
      
        $this->apiInstance = new \SendinBlue\Client\Api\ContactsApi(
            new \GuzzleHttp\Client(),
            $config
        );

        return parent::beforeAction($action);

    }

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
        $request  = Craft::$app->getRequest();
        
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
        
        // Check to see if contact exists
        $contactExists  = $this->contactExists($signup);

        // if success is false  and error code is document_not_found the contact does not exist so create it
        if ($contactExists['success'] == false && $contactExists['errors']['signupError']['code'] == 'document_not_found') {
            $createContact = $this->createContact($signup);
            
            // if the creation fails return false with errors
            if (!$createContact['success']) {
                $response['success'] = false;
                $response['errors']  = $createContact['errors'];
                //return $this->asJson($response);
            }
        }
    
        if ($contactExists['success'] == true && isset($contactExists['contact'])) {
            $contact = $contactExists['contact'];
        }
        
        // Handle contact existing but not in specified lists
        if (isset($contact)) {
            // Get the lists the contact doesn't blong to
            $contactsInAllLists = $this->contactsInAllLists($contact);
            if ($contactsInAllLists['success'] == false && count($contactsInAllLists['listsToAdd']) > 0 ) {
                
                $addContactToLists =  $this->addContactToLists($contact, $contactsInAllLists['listsToAdd']);
                // If addContactToLists fails return the errors
                if (!$addContactToLists['success']) {
                    $response['success'] = false;
                    $response['errors']  = $addContactToLists['errors'];
                   // return $this->asJson($response);
                }
            }
        }
      
        
        return $this->asJson($response);
    }

    /**
    * Handle the creation of a new contact in Send In Blue
    *
    * @return Json
    */
    protected function createContact(Signup $signup)
    {
                
        // Build the SIB details
        $response = [];
        $response['success'] = true;

        $lists = explode(",", $this->pluginSettings->sibLists);

        // Turn string id's into ints for the SIB API
        foreach ($lists as $key => $id) {
            $lists[$key] = intval($id);
        }

        $contactConf = ['email' => $signup->email, 'listIds' => $lists];
 
        // Create SIB contact object
        $createContact = new \SendinBlue\Client\Model\CreateContact($contactConf);

        try {
            // Send the contact to SIB
            $this->apiInstance->createContact($createContact);
        } catch (\SendinBlue\Client\ApiException $e) {
            
            // Decipher error message from the SendinBlue API Wrapper getting only the error message
            $exception = json_decode($e->getResponseBody(), true);
            // Handle the different error codes
            $response['success'] = false;
            $response['errors']  = $this->handleErrorCodes($exception, "signupError");
        }

        return $response;
    }

    /**
     * Check to see if the contact exists within SIB
     *
     * @return Array
     */
    protected function contactExists(Signup $signup)
    {
        // Set up defaults & values
        $response            = [];
        $response['success'] = false;

        try {
            // If the contact exists return true
            $contact = $this->apiInstance->getContactInfo($signup->email);
            if ($contact->getEmail()) {
                $response['success'] = true;
                $response['contact'] = $contact;
            }
        } catch (\SendinBlue\Client\ApiException $e) {
               
            // Decipher error message from the SendinBlue API Wrapper getting only the error message
            $exception = json_decode($e->getResponseBody(), true);
            $response['success'] = false;
            $response['errors']  = $this->handleErrorCodes($exception, "signupError");
        }
        return $response;
    }


    /**
     * Check to see if the contact is in the specified lists
     * return the list ids that the contact does not belong to
     *
     * @return array
     */
    protected function contactsInAllLists($contact)
    {
        $response               = [];
        $response['success']    = true;
        $response['listsToAdd'] = [];
        $contactsLists          = $contact->getListIds();
        $lists                  = explode(",", $this->pluginSettings->sibLists);

        foreach ($lists as $listId) {
            if (!in_array(intval($listId), $contactsLists)) {
                $response['success'] = false;
                array_push($response['listsToAdd'], intval($listId));
            }
        }

        return $response;
    }


    public function addContactToLists($contact, $lists)
    {
        $response            = [];
        $response['success'] = true;
        // Add email to an array and create AddContactToList model
        $emails = ['emails'=>[$contact->getEmail()]];
        $contactEmails = new \SendinBlue\Client\Model\AddContactToList($emails); // \SendinBlue\Client\Model\AddContactToList | Emails addresses of the contacts
     
        try {
            // Attempt to the contact to each list
            foreach ($lists as $listId) {                     
                $this->apiInstance->addContactToList($listId, $contactEmails);
            }

        } catch (\SendinBlue\Client\ApiException $e) {

            // Decipher error message from the SendinBlue API Wrapper getting only the error message
            $exception = json_decode($e->getResponseBody(), true);
            $response['success'] = false;
            $response['errors']  = $this->handleErrorCodes($exception, "signupError");
        }

        return $response;
    }

    /**
     * Handle the errors passed back from the SIB API
     *
     * @return Array
     */
    protected function handleErrorCodes($exception, $type = 'signupError')
    {
        $errorArray = [];

        switch ($exception['code']) {
            case 'document_not_found':
                $error = [ "code" => "document_not_found", "message" => "Contact does not exist"];
                 $errorArray = [$type => $error];
            break;
            case 'duplicate_parameter':
                $error = [ "code" => "duplicate_parameter", "message" => "Contact already Exists" ];
                $response['errors'] = ['signupError' => $error];
            break;
            default:
                 $errorArray = [$type => $exception];
            break;
        }
        return $errorArray;
    }
}
