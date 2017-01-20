define([
        'emberjs',
        'text!./NewsletterView.html',
        'Shared/I18n'
    ],
    function (Ember,
              template,
              I18n) {
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
                }.observes('value'),
            }),
            selectContent: [],
            subscription: null,
            errorMessage: null,
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
            init: function () {
                var subscriptionsEndpoint = '/newsletter/getSubscriptions';
                var request = new XMLHttpRequest();
                request.withCredentials = true;
                request.open('GET', subscriptionsEndpoint, true);

                request.onload = function () {
                    if (request.status >= 200 && request.status < 400) {
                        var response = JSON.parse(request.responseText);
                        this.set('selectContent', response);
                    } else {
                        this.set('errorMessage', 'js.error');
                    }
                }.bind(this);

                request.onerror = function () {
                    this.set('errorMessage', 'js.error');
                }.bind(this);
                request.send();
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

                var request = new XMLHttpRequest();
                request.withCredentials = true;
                request.open('POST', sendEndpointUrl, true);
                request.onload = function () {
                    if (request.status >= 200 && request.status < 400) {
                        var response = JSON.parse(request.responseText);
                        if (response.status == 'success') {
                            this.set('buttonLabel', 'js.sent');
                        } else {
                            this.set('errorMessage', 'js.error');
                        }
                    } else {
                        this.set('errorMessage', 'js.error');
                    }
                }.bind(this);

                request.onerror = function () {
                    this.set('errorMessage', 'js.error');
                }.bind(this);

                var formData = new FormData();
                formData.append('subscription', subscription);
                formData.append('node', this.get('controller.nodeProperties._path'));
                request.send(formData);

                this.set('buttonLabel', 'js.sending');
            }
        });
    });
