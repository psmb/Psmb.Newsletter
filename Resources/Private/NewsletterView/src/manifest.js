import manifest from '@neos-project/neos-ui-extensibility';
import NewsletterView from './NewsletterView';

manifest('Psmb.Newsletter:NewsletterView', {}, globalRegistry => {
    const viewsRegistry = globalRegistry.get('inspector').get('views');

    viewsRegistry.set('Psmb.Newsletter/Views/NewsletterView', {
        component: NewsletterView
    });
});
