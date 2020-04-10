<?php

namespace wc4bcraftsibcontactform\craftsibcontactform;

use Craft;
use wc4bcraftsibcontactform\craftsibcontactform\events\SignupEvent;
use wc4bcraftsibcontactform\craftsibcontactform\models\Signup;
use wc4bcraftsibcontactform\craftsibcontactform\Craftsibcontactform;

use yii\base\Component;
use yii\base\InvalidConfigException;

class Subscriber extends Component
{
    // Constants
    // =========================================================================

    /**
     * @event SubmissionEvent The event that is triggered before a message is sent
     */
    const EVENT_BEFORE_SIGNUP = 'beforeSignup';

    /**
     * @event SubmissionEvent The event that is triggered after a message is sent
     */
    const EVENT_AFTER_SIGNUP = 'afterSignup';

    protected $apiInstance;
    protected $pluginSettings;

    // Public Methods
    // =========================================================================

    function __construct() {

        $this->pluginSettings = Craftsibcontactform::getInstance()->getSettings();
        $apiKey =  $this->pluginSettings->sibApiKey;
        $config = \SendinBlue\Client\Configuration::getDefaultConfiguration()->setApiKey('api-key', $apiKey);
      
        $this->apiInstance = new \SendinBlue\Client\Api\ContactsApi(
            new \GuzzleHttp\Client(),
            $config
        );
    }

    /**
     * Sends an email submitted through a contact form.
     *
     * @param Submission $submission
     * @throws InvalidConfigException if the Craftsibcontactform settings don't validate
     * @return array
     */
    public function signup(Signup $signup): array
    {
        $response            = [];
        $response['success'] = true;

         // Check to see if contact exists
        $contactExists  = $this->contactExists($signup);

        // Trigger before Mail event
        $event = new SignupEvent([
            'signup' => $signup,
        ]);
        $this->trigger(self::EVENT_BEFORE_SIGNUP, $event);



        // if success is false  and error code is document_not_found the contact does not exist so create it
        if ($contactExists['success'] == false && $contactExists['errors']['signupError']['code'] == 'document_not_found') {
            $createContact = $this->createContact($signup);
            
            // if the creation fails return false with errors
            if (!$createContact['success']) {
                $response['success'] = false;
                $response['errors']  = $createContact['errors'];
                return $response;
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
                   return $response;
                }
            }
        }

        // Trigger before Mail event
        $event = new SignupEvent([
        'signup' => $signup,
        ]);
        $this->trigger(self::EVENT_AFTER_SIGNUP, $event);

        return $response;
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
