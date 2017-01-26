<?php
namespace Psmb\Newsletter\Controller;

use Psmb\Newsletter\Domain\Model\Subscriber;
use Psmb\Newsletter\Domain\Repository\SubscriberRepository;
use Psmb\Newsletter\Service\FusionMailService;
use TYPO3\Flow\Mvc\View\JsonView;
use TYPO3\Flow\I18n\Service as I18nService;
use TYPO3\Flow\I18n\Translator;
use TYPO3\Flow\Mvc\Controller\ActionController;
use TYPO3\Flow\Annotations as Flow;
use function TYPO3\Flow\var_dump;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

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
        $this->fusionMailService->setupObject($this->getControllerContext(), $this->request);
        $subscriptions = array_filter($this->subscriptions, function ($item) use ($subscription) {
            return $item['identifier'] == $subscription;
        });
        $nestedLetters = array_map(function ($subscription) use ($node) {
            return $this->generateLettersForSubscription($subscription, $node);
        }, $subscriptions);
        $letters = array_reduce($nestedLetters, function ($acc, $item) {
            return array_merge($acc, $item);
        }, []);

        array_map(function($letter) {
            $this->fusionMailService->sendLetter($letter);
        }, $letters);
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
        $this->fusionMailService->setupObject($this->getControllerContext(), $this->request);
        $subscriptions = array_filter($this->subscriptions, function ($item) use ($subscription) {
            return $item['identifier'] == $subscription;
        });

        $subscriber = new Subscriber();
        $subscriber->setEmail($email);
        $subscriber->setName('Test User');

        $letter = $this->fusionMailService->generateSubscriptionLetter($subscriber, $subscriptions[0], $node);
        $this->fusionMailService->sendLetter($letter);

        $this->view->assign('value', ['status' => 'success']);
    }

    /**
     * Generate a letter for each subscriber in the subscription
     *
     * @param array $subscription
     * @param NodeInterface $node Node of the current newsletter item
     * @return array Array of letters
     */
    protected function generateLettersForSubscription($subscription, $node)
    {
        $subscribers = $this->subscriberRepository->findBySubscriptionId($subscription['identifier'])->toArray();

        $letters = array_map(function ($subscriber) use ($subscription, $node) {
            return $this->fusionMailService->generateSubscriptionLetter($subscriber, $subscription, $node);
        }, $subscribers);
        return array_filter($letters);
    }

}
