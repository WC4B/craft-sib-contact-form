# craft sib contact form plugin for Craft CMS 3.x

A contact form integration for the Send in Blue API

![Screenshot](resources/img/plugin-logo.png)

# Requirements

This plugin requires Craft CMS 3.0.0-beta.23 or later.

# Installation

To install the plugin, follow these instructions.

1. Open your terminal and go to your Craft project:

        cd /path/to/project

2. Then tell Composer to load the plugin:

        composer require sib-contact-form-integration/sib-contact-form-integration

3. In the Control Panel, go to Settings → Plugins and click the “Install” button for sib-contact-form-integration.


## craft sib contact form Overview

This plugin is a fork of the [craftcms/contact-form](https://github.com/craftcms/contact-form) and uses its base contact form logic in to send emails via the users SMPT email settings. 
In adition to this this plugin has additional settings and logic to handle talking to the [Send In Blue API](https://developers.sendinblue.com/).
This plugin lets you add contacts to your SendInBlue contact lists and add those contacts to mailing lists.

# Configuring sib-contact-form-integration

## CMS Settings
Within the craft CMS, go to the settings page and click on the `sib-contact-form-integration` settings. 
From here you can add your Send In Blue API Key as `SIB API Key` and the newsletter list ID's comma separated within the `SIB Lists` textbox.

## Custom Logic

To add custom logic the plugin has extended the `afterValidate` and `beforeSend` event hooks from  original [craftcms/contact-form](https://github.com/craftcms/contact-form).

To use the following event hooks add them to a `sib-contact-form-integration.php` file in your projects `/config` directory.

e.g. `my-project/config/sib-contact-form-integration.php`

### afterValidate
The plugin has the original `afterValidate` but uses the plugins namespacing.
This is where you can add custom validation for your form elements.
For example.

```<?php
use wc4b\sibcontactformintegration\models\Submission;
use yii\base\Event;


Event::on(Submission::class, Submission::EVENT_AFTER_VALIDATE, function(Event $e) {
    /** @var Submission $submission */
    $submission = $e->sender;

    // Make sure that `message[fromName]` was filled in
    if (!isset($submission->fromName) || empty($submission->fromName)) {
        // Add the error
        // (This will be accessible via `message.getErrors('message.phone')` in the template.)
        $submission->addError('fromName', "Name cannot be blank");
    }
    
    // Make sure that `message[termsCondCheck]` was filled in
    if (!isset($submission->message['termsCondCheck']) || empty($submission->message['termsCondCheck'])) {
        // Add the error
        // (This will be accessible via `message.getErrors('message.phone')` in the template.)
        $submission->addError('message.termsCondCheck', "Please review our T'&C's");
    }
});
```

### beforeSend & afterSend
The plugin has the original `beforeSend` but uses the plugins namespacing.
The plugins has also added and extra `afterSend` event hook to use.
This is where you can add custom validation for your form elements.

For example.

```<?php 
use wc4b\sibcontactformintegration\events\MailEvent;
use wc4b\sibcontactformintegration\Mailer;
use yii\base\Event;


Event::on(Mailer::class, Mailer::EVENT_BEFORE_SEND || Mailer::EVENT_AFTER_SEND , function(MailEvent $e) {

});
```

### beforeSignup & afterSignup
In addition to the above the plugin also has event hooks when signing up users to the Send In Blue mailing lists.

For example.

```<?php 
use wc4b\sibcontactformintegration\events\SignupEvent;
use wc4b\sibcontactformintegration\Subscriber;
use yii\base\Event;


Event::on(Subscriber::class, Subscriber::EVENT_BEFORE_SIGNUP || Subscriber::EVENT_AFTER_SIGNUP  , function(SignupEvent $e) {

});
```


## Using sib-contact-form-integration

You can use the contact form via a page submission as indictated in the [craftcms/contact-form](https://github.com/craftcms/contact-form) docs. 
That being said it is recomended to use a `Ajax` request to handle the form submission for increased user experience. 

and example module might look like this 
```/**/
const contactFormSubmission = (function () {

    const axios = require('axios').default;
    const DOM = {};
    function init(formElement) {
        DOM.form  = formElement;
        addEvents();

    }
    function addEvents() {

        DOM.form.addEventListener("submit", function (e) {

            e.preventDefault();
            var data = new FormData(DOM.form)

            handleReset();

            axios.post('/', data)
                .then(function (response) {
                    if (response.data.errors) {
                        handleErrors(response.data);
                    }
                    else {
                        handleSuccess();
                    }
                })
                .catch(function (error) {
                    console.log(error);
                });
        });
    }

    function handleSuccess() {
       
        var submitButton = DOM.form.getElementsByClassName("submit-button");
        submitButton[0].classList.add("inactive");

        var success = DOM.form.getElementsByClassName("success-message");
        success[0].classList.add("active");


        var inputs = DOM.form.getElementsByClassName("form-input");

        for (let index = 0; index < inputs.length; index++) {
            var element = inputs[index];

            element.value = "";
            element.checked = false;

        }

    }

    function handleReset() {

        var errorMessages = DOM.form.getElementsByClassName("error-message");

        for (let index = 0; index < errorMessages.length; index++) {
            var element = errorMessages[index];
            element.innerHTML = "";
            element.classList.remove('active');
        }

    }

    function handleErrors(data) {

        if (data.errors) {
            Object.keys(data.errors).forEach(function (key) {
                var string = "";

                data.errors[key].forEach(function (e) {
                    string += "<p>" + e + "<p>";
                });

                var errorDivs = DOM.form.getElementsByClassName("error-message");
                var errorDiv = null;

                for (let index = 0; index < errorDivs.length; index++) {
                    const element = errorDivs[index];
                    
                    if(element.id == key + ".errors"){                        
                        var errorDiv = element;
                        errorDiv.innerHTML = string;
                        errorDiv.classList.add('active');
                    }
                }
              
            });
        }   

    }

    return {
        init: init
    };


})('Contact Form Submission');


document.addEventListener("DOMContentLoaded", function () {
    var form = document.getElementById("contact-form");
    if (form) {
        contactFormSubmission.init(form);
    }
});
```

With the following template 

```	.
<form id="contact-form" method="post" accept-charset="UTF-8">
    {{ csrfInput() }}
    <input type="hidden" name="action" value="sib-contact-form-integration/mail">

    <div class="py-2">
        <div>
            <input id="from-name" type="text" name="fromName" class="form-input p-4 w-full" placeholder="Name" value="{{ message.fromName ?? '' }}">
        </div>
        <div id="fromName.errors" class="error-message w-full bg-primary text-white p-2"></div>
    </div>

    <div class="py-2">
        <div>
            <input id="from-email" type="email" name="fromEmail" class="form-input p-4 w-full" placeholder="Email" value="{{ message.fromEmail ?? '' }}">
        </div>
        <div id="fromEmail.errors" class="error-message w-full bg-primary text-white p-2"></div>
    </div>

    <div class="py-2">
        <div>
            <textarea rows="10" cols="40" id="message" class="form-input p-4 w-full" placeholder="Message" name="message[body]">{{ message.message.body ?? '' }}</textarea>
        </div>
        <div id="message.errors" class="error-message w-full bg-primary text-white p-2"></div>
    </div>

    <div class="py-2 text-white">
        <div>
            <input class="form-input styled-checkbox" id="message[termsCondCheck]" type="checkbox" name="message[termsCondCheck]" value="true">
            <label for="message[termsCondCheck]">I have read the and agree to the <a href="/privacy-policy" class="c-link c-link--primary" targer="_blank" rel="noreferrer noopener" el>Privacy Policy<a></label>
        </div>
        <div id="message.termsCondCheck.errors" class="error-message w-full bg-primary text-white p-2"></div>
    </div>


    <div class="py-2 text-white">
        <div>
            <input class="form-input styled-checkbox" id="message[newsletterSignup]" type="checkbox" name="message[newsletterSignup]" value="true">
            <label for="message[newsletterSignup]">I want to sign up to the newsletter</label>

        </div>
        <div id="message.newsletterSignup.errors" class="error-message w-full bg-primary text-white p-2"></div>
    </div>

    <div class="py-2 relative">
        <div class="success-message">
            <h2 class="text-white">Thanks for your message</h2>
        </div>
        <div class="submit-button">
            <button type="submit" class="c-btn c-btn--ghost-nearly-black">Subscribe</button>
        </div>
    </div>
</form>
```

## sib-contact-form-integration Roadmap

Some things to do, and ideas for potential features:

* Option to make terms and conditions check mandatory

Brought to you by [WillCodeForBeer](https://github.com/WC4B/sib-contact-form-integration)
