define(
    [
        'emberjs',
        'Shared/AbstractModal',
        'text!./TestConfirmation.html'
    ],
    function (Ember, AbstractModal, template) {
        return AbstractModal.extend({
            template: Ember.Handlebars.compile(template),
            testEmail: null,
            sendingDisabled: function () {
                return !this.get('testEmail');
            }.property('testEmail')
        });
    });
