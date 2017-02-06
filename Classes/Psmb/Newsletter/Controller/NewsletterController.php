<?php
namespace Psmb\Newsletter\Controller;

use Flowpack\JobQueue\Common\Annotations as Job;
use Neos\Flow\Annotations as Flow;
use Psmb\Newsletter\Domain\Model\Subscriber;
use Psmb\Newsletter\Domain\Repository\SubscriberRepository;
use Psmb\Newsletter\Service\FusionMailService;
use Neos\Flow\Mvc\View\JsonView;
use Neos\Flow\I18n\Service as I18nService;
use Neos\Flow\I18n\Translator;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\ContentRepository\Domain\Model\NodeInterface;

class NewsletterController extends ActionController
{
    /**
     * @Flow\Inject
     * @var I18nService
     */
    protected $i18nService;

    /**
     * @Flow\Inject
     * @var Translator
     */
    protected $translator;
    /**
     * @Flow\Inject
     * @var FusionMailService
     */
    protected $fusionMailService;

    /**
     * @Flow\Inject
     * @var SubscriberRepository
     */
    protected $subscriberRepository;

    /**
     * @Flow\InjectConfiguration(path="subscriptions")
     * @var array
     */
    protected $subscriptions;

    /**
     * @var array
     */
    protected $viewFormatToObjectNameMap = array(
        'json' => JsonView::class
    );

    /**
     * Get manual subscriptions for AJAX sending
     *
     * @return void
     */
    public function getSubscriptionsAction() {
        $manualSubscriptions = array_filter($this->subscriptions, function ($item) {
            return $item['interval'] == 'manual';
        });
        $subscriptionsJsonArray = array_map(function ($item) {
            return ['label' => $item['label'], 'value' => $item['identifier']];
        }, $manualSubscriptions);
        $this->view->assign('value', array_values($subscriptionsJsonArray));
    }

    /**
     * Registers a new subscriber
     *
     * @param string $subscription Subscription id to send newsletter to
     * @param NodeInterface $node Node of the current newsletter item
     * @return void
     */
    public function sendAction($subscription, NodeInterface $node)
    {
        $subscriptions = array_filter($this->subscriptions, function ($item) use ($subscription) {
            return $item['identifier'] == $subscription;
        });
        array_walk($subscriptions, function ($subscription) use ($node) {
            $this->sendLettersForSubscription($subscription, $node);
        });
        $this->view->assign('value', ['status' => 'success']);
    }

    /**
     * Sends a test letter for subscription
     *
     * @param string $subscription Subscription id to send newsletter to
     * @param NodeInterface $node Node of the current newsletter item
     * @param string $email Test email address
     * @return void
     */
    public function testSendAction($subscription, NodeInterface $node, $email)
    {
        $subscriptions = array_filter($this->subscriptions, function ($item) use ($subscription) {
            return $item['identifier'] == $subscription;
        });
        $subscription = reset($subscriptions);

        $subscriber = new Subscriber();
        $subscriber->setEmail($email);
        $subscriber->setName('Test User');

        $this->fusionMailService->generateSubscriptionLetterAndSend($subscriber, $subscription, $node);

        $this->view->assign('value', ['status' => 'success']);
    }

    /**
     * Generate a letter for each subscriber in the subscription
     *
     * @Job\Defer(queueName="psmb-newsletter-web")
     * @param array $subscription
     * @param NodeInterface $node Node of the current newsletter item
     * @return void
     */
    public function sendLettersForSubscription($subscription, $node)
    {
        $subscribers = $this->subscriberRepository->findBySubscriptionId($subscription['identifier'])->toArray();

        array_walk($subscribers, function ($subscriber) use ($subscription, $node) {
            $this->fusionMailService->generateSubscriptionLetterAndSend($subscriber, $subscription, $node);
        });
    }

}
