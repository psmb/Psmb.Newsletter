define(
    [
        'emberjs',
        'Shared/AbstractModal',
        'text!./Confirmation.html'
    ],
    function (Ember, AbstractModal, template) {
        return AbstractModal.extend({
            template: Ember.Handlebars.compile(template)
        });
    });