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
use wc4bcraftsibcontactform\craftsibcontactform\Craftsibcontactform as Plugin;
use craft\web\Controller;
use craft\web\UploadedFile;
use yii\web\Response;
/**
 * Mail Controller
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
class MailController extends Controller
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
     * e.g.: actions/craft-sib-contact-form/mail
     *
     * @return mixed
     */
    public function actionIndex()
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();
        $plugin = Plugin::getInstance();
        $settings = $plugin->getSettings();

        $submission = new Submission();
        $submission->fromEmail = $request->getBodyParam('fromEmail');
        $submission->fromName = $request->getBodyParam('fromName');
        $submission->subject = $request->getBodyParam('subject');

        $message = $request->getBodyParam('message');
        if (is_array($message)) {
            $submission->message = array_filter($message, function($value) {
                return $value !== '';
            });
        } else {
            $submission->message = $message;
        }

        if ($settings->allowAttachments && isset($_FILES['attachment']) && isset($_FILES['attachment']['name'])) {
            if (is_array($_FILES['attachment']['name'])) {
                $submission->attachment = UploadedFile::getInstancesByName('attachment');
            } else {
                $submission->attachment = [UploadedFile::getInstanceByName('attachment')];
            }
        }

        if (!$plugin->getMailer()->send($submission)) {
            if ($request->getAcceptsJson()) {
                return $this->asJson(['errors' => $submission->getErrors()]);
            }

            Craft::$app->getSession()->setError(Craft::t('craft-sib-contact-form', 'There was a problem with your submission, please check the form and try again!'));
            Craft::$app->getUrlManager()->setRouteParams([
                'variables' => ['message' => $submission]
            ]);

            return null;
        }

        if ($request->getAcceptsJson()) {
            return $this->asJson(['success' => true]);
        }

        Craft::$app->getSession()->setNotice($settings->successFlashMessage);
        return $this->redirectToPostedUrl($submission);
    }

    /**
     * Handle a request going to our plugin's actionDoSomething URL,
     * e.g.: actions/craft-sib-contact-form/mail/do-something
     *
     * @return mixed
     */
    public function actionDoSomething()
    {
        $result = 'Welcome to the MailController actionDoSomething() method';

        return $result;
    }
}
