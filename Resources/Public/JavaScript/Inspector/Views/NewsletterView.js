define([
        'emberjs',
        'text!./NewsletterView.html',
        'Shared/I18n',
        'Shared/HttpClient',
        './Confirmation',
        './TestConfirmation'
    ],
    function (Ember,
              template,
              I18n,
              HttpClient,
              Confirmation,
              TestConfirmation) {
        return Ember.View.extend({
            template: Ember.Handlebars.compile(template),

            subscription: null,
            doneLoading: false,

            select: Ember.Select.extend({
                content: function () {
                    return this.get('parentView.selectContent');
                }.property('parentView.selectContent'),
                optionValuePath: 'content.value',
                optionLabelPath: 'content.label',
                prompt: I18n.translate('Psmb.Newsletter:Main:js.selectSubscription', 'Please select a subscription'),
                valueDidChange: function() {
                    this.set('parentView.subscription', this.get('value'));
                    this.set('parentView.buttonLabelId', 'js.send');
                }.observes('value')
            }),
            selectContent: null,

            notificationMessageId: null,
            notificationMessage: function () {
                return I18n.translate('Psmb.Newsletter:Main:' + this.get('notificationMessageId'), '');
            }.property('notificationMessageId'),

            errorMessageId: null,
            errorMessage: function () {
                return I18n.translate('Psmb.Newsletter:Main:' + this.get('errorMessageId'), '');
            }.property('errorMessageId'),

            buttonLabelId: 'js.send',
            buttonLabel: function () {
                return I18n.translate('Psmb.Newsletter:Main:' + this.get('buttonLabelId'), 'Send');
            }.property('buttonLabelId'),

            testButtonLabelId: 'js.send',
            testButtonLabel: function () {
                return I18n.translate('Psmb.Newsletter:Main:' + this.get('testButtonLabelId'), 'Send');
            }.property('testButtonLabelId'),

            didInsertElement: function () {
                var subscriptionsEndpoint = '/newsletter/getSubscriptions';

                var callback = function (response) {
                    if (response.length === 1) {
                        this.set('subscription', response[0].value);
                        this.set('doneLoading', true);
                    } else if (response.length > 1){
                        this.set('selectContent', response);
                        this.set('doneLoading', true);
                    } else {
                        this.set('errorMessageId', 'js.error');
                    }
                }.bind(this);
                var data = {nodeType: this.get('controller.nodeProperties._nodeType')};
                HttpClient.getResource(subscriptionsEndpoint, {data: data}).then(callback);

                return this._super();
            },
            finalSend: function () {
                var subscription = this.get('subscription');
                if (!subscription) {
                    this.set('errorMessageId', 'js.selectSubscription');
                    return;
                }
                this.set('errorMessageId', null);

                Confirmation.create({
                    sendRequest: this.sendRequest.bind(this),
                    sendNewsletter: function () {
                        this.sendRequest();
                        this.cancel();
                    }
                });
            },
            testSend: function () {
                var subscription = this.get('subscription');
                if (!subscription) {
                    this.set('errorMessageId', 'js.selectSubscription');
                    return;
                }
                this.set('errorMessageId', null);

                TestConfirmation.create({
                    sendRequest: this.sendRequest.bind(this),
                    sendNewsletter: function () {
                        var testEmail = this.get('testEmail');
                        if (testEmail) {
                            this.sendRequest(testEmail);
                        }
                        this.cancel();
                    }
                });
            },
            sendRequest: function (testEmail) {
                var sendEndpointUrl = testEmail ? '/newsletter/testSend' : '/newsletter/send';

                var callback = function (response) {
                    if (response.status === 'success') {
                        this.set('notificationMessageId', 'js.sent');
                    } else {
                        this.set('errorMessageId', 'js.error');
                    }
                }.bind(this);

                // TODO: can we do it cleaner?
                var siteContextPath = document.getElementById('neos-document-metadata').dataset.neosSiteNodeContextPath;
                var context = siteContextPath.split('@')[1] || '';
                var dimensions = (';' + context.split(';')[1]) || '';
                var nodeContextPath = this.get('controller.nodeProperties._path') + '@live' + dimensions;
                var data = {
                    subscription: this.get('subscription'),
                    node: nodeContextPath
                };

                if (testEmail) {
                    data.email = testEmail;
                }
                HttpClient.createResource(sendEndpointUrl, {data: data}).then(callback);
                this.set('notificationMessageId', 'js.sending');
            }
        });
    });
