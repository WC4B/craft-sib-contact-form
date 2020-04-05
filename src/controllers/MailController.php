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


use Craft;
use wc4bcraftsibcontactform\craftsibcontactform\models\Submission;
use wc4bcraftsibcontactform\craftsibcontactform\Craftsibcontactform;
use craft\web\Controller;
use craft\web\UploadedFile;
use yii\web\Response;
/**
 * Mail Controller
 *
 * @author    Joel Beer
 * @package   Craftsibcontactform
 * @since     1.0.0
 */
class MailController extends Controller
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
     * e.g.: actions/craft-sib-contact-form/mail
     *
     * @return mixed
     */
    public function actionIndex()
    {
        // Get the post request
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();
        $plugin = Craftsibcontactform::getInstance();
        $settings = $plugin->getSettings();

        // Create a new Submission & Assign it data from the post request
        $submission = new Submission();
        $submission->fromEmail = $request->getBodyParam('fromEmail');
        $submission->fromName = $request->getBodyParam('fromName');
        $submission->subject = $request->getBodyParam('subject');

        // If the Message is not an array use it as the main message
        $message = $request->getBodyParam('message');
        if (is_array($message)) {
            // if it is an array clear out any instances where the value is null
            $submission->message = array_filter($message, function($value) {
                // TODO: do we want to filter out null vars ? it could be usefull information?
                return $value !== '';
            });
        } else {
            $submission->message = $message;
        }

        // If attachments are allowed handle them 
        if ($settings->allowAttachments && isset($_FILES['attachment']) && isset($_FILES['attachment']['name'])) {
            if (is_array($_FILES['attachment']['name'])) {
                $submission->attachment = UploadedFile::getInstancesByName('attachment');
            } else {
                $submission->attachment = [UploadedFile::getInstanceByName('attachment')];
            }
        }

        // If this fail handle the errors 
        // send() handles the Submission Validation
        if (!$plugin->getMailer()->send($submission)) {

            // if the request accepts json return it as json
            if ($request->getAcceptsJson()) {
                return $this->asJson(['errors' => $submission->getErrors()]);
            }

            // If the request does not allow json set the session variables to use on the front end.
            Craft::$app->getSession()->setError(Craft::t('craft-sib-contact-form', 'There was a problem with your submission, please check the form and try again!'));
            Craft::$app->getUrlManager()->setRouteParams([
                'variables' => ['message' => $submission]
            ]);

            return null;
        }

        // If this is run the send was successfull and can return true json
        if ($request->getAcceptsJson()) {
            return $this->asJson(['success' => true]);
        }
        // if request does not allow json set the session flash message to be displayed on the front end
        Craft::$app->getSession()->setNotice($settings->successFlashMessage);
        return $this->redirectToPostedUrl($submission);
    }

}
