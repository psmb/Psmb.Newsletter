define([
        'emberjs',
        'text!./NewsletterView.html',
        'Shared/I18n',
        'Shared/HttpClient'
    ],
    function (Ember,
              template,
              I18n,
              HttpClient) {
        return Ember.View.extend({
            template: Ember.Handlebars.compile(template),
            _select: Ember.Select.extend({
                content: function () {
                    return this.get('parentView.selectContent');
                }.property('parentView.selectContent'),
                optionValuePath: 'content.value',
                optionLabelPath: 'content.label',
                prompt: I18n.translate('Psmb.Newsletter:Main:js.selectSubscription', 'Please select a subscription'),
                valueDidChange: function() {
                    this.set('parentView.subscription', this.get('value'));
                    this.set('parentView.buttonLabel', 'js.send');
                }.observes('value')
            }),
            selectContent: null,
            subscription: null,
            errorMessage: null,
            doneLoading: false,
            _errorMessage: function () {
                return I18n.translate('Psmb.Newsletter:Main:' + this.get('errorMessage'), '');
            }.property('errorMessage'),
            buttonLabel: 'js.send',
            _buttonLabel: function () {
                return I18n.translate('Psmb.Newsletter:Main:' + this.get('buttonLabel'), 'Send');
            }.property('buttonLabel'),
            sendingDisabled: function () {
                return this.get('buttonLabel') !== 'js.send';
            }.property('buttonLabel'),
            _sendTo: function () {
                return I18n.translate('Psmb.Newsletter:Main:js.sendTo', 'Send newsletter to: ');
            }.property(),
            _loadingLabel: function () {
                return I18n.translate('Psmb.Newsletter:Main:js.loading', 'Loading...');
            }.property(),
            init: function () {
                var subscriptionsEndpoint = '/newsletter/getSubscriptions';

                var callback = function (response) {
                    if (response.length === 1) {
                        this.set('subscription', response[0].value);
                        this.set('doneLoading', true);
                    } else if (response.length > 1){
                        this.set('selectContent', response);
                        this.set('doneLoading', true);
                    } else {
                        this.set('errorMessage', 'js.error');
                    }
                }.bind(this);
                HttpClient.getResource(subscriptionsEndpoint).then(callback);

                return this._super();
            },
            send: function () {
                var subscription = this.get('subscription');
                if (!subscription) {
                    this.set('errorMessage', 'js.selectSubscription');
                    return;
                }
                this.set('errorMessage', null);

                var sendEndpointUrl = '/newsletter/send';

                var callback = function (response) {
                    if (response.status == 'success') {
                        this.set('buttonLabel', 'js.sent');
                    } else {
                        this.set('errorMessage', 'js.error');
                    }
                }.bind(this);
                var data = {
                    subscription: subscription,
                    node: this.get('controller.nodeProperties._path')
                };
                HttpClient.createResource(sendEndpointUrl, {data: data}).then(callback);
                this.set('buttonLabel', 'js.sending');
            }
        });
    });
