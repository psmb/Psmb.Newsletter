<?php
namespace Psmb\Newsletter\Controller;

use Flowpack\JobQueue\Common\Annotations as Job;
use Neos\Flow\Annotations as Flow;
use Psmb\Newsletter\Domain\Model\Subscriber;
use Psmb\Newsletter\Service\FusionMailService;
use Psmb\Newsletter\Service\SubscribersService;
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
     * @var SubscribersService
     */
    protected $subscribersService;

    /**
     * @Flow\Inject
     * @var \Neos\Flow\Log\SystemLoggerInterface
     */
    protected $systemLogger;

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
     * @param string $nodeType
     * @return void
     */
    public function getSubscriptionsAction($nodeType) {
        $subscriptions = array_filter($this->subscriptions, function ($item) use ($nodeType) {
            if (isset($item['sendFromUiNodeType'])) {
                return $item['sendFromUiNodeType'] == $nodeType;
            }
            return false;
        });
        if(!count($subscriptions)) {
            $subscriptions = array_filter($this->subscriptions, function ($item) {
                return $item['interval'] == 'manual';
            });
        }
        $subscriptionsJsonArray = array_map(function ($item) {
            return ['label' => isset($item['label']) ? $item['label'] : '', 'value' => $item['identifier']];
        }, $subscriptions);
        $this->view->assign('value', array_values($subscriptionsJsonArray));
    }

    /**
     * Registers a new subscriber
     *
     * @param string $subscription Subscription id to send newsletter to
     * @param NodeInterface $node Node of the current newsletter item
     * @param array $dataSourceAdditionalArguments
     * @return void
     */
    public function sendAction($subscription, NodeInterface $node, array $dataSourceAdditionalArguments = null)
    {
        $subscriptions = array_filter($this->subscriptions, function ($item) use ($subscription) {
            return $item['identifier'] == $subscription;
        });
        array_walk($subscriptions, function ($subscription) use ($node) {
            $subscription['isSendFromUi'] = true;
            if ($dataSourceAdditionalArguments) {
                $subscription['dataSourceAdditionalArguments'] = $dataSourceAdditionalArguments;
            }
            $this->sendLettersForSubscription($subscription, $node);
        });
        $this->view->assign('value', ['status' => 'success']);
    }

    /**
     * Registers a new subscriber
     *
     * @param string $subscription Subscription id to send newsletter to
     * @param array $dataSourceAdditionalArguments
     * @return void
     */
    public function previewAction($subscription, $dataSourceAdditionalArguments = null)
    {
        $subscriptions = array_filter($this->subscriptions, function ($item) use ($subscription) {
            return $item['identifier'] == $subscription;
        });
        $subscription = reset($subscriptions);
        if ($dataSourceAdditionalArguments) {
            $subscription['dataSourceAdditionalArguments'] = $dataSourceAdditionalArguments;
        }
        $subscribers = $this->subscribersService->getSubscribers($subscription);
        $this->view->assign('value', $subscribers);
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
        $subscription['isSendFromUi'] = true;

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
        $subscribers = $this->subscribersService->getSubscribers($subscription);

        array_walk($subscribers, function ($subscriber) use ($subscription, $node) {
            try {
                $this->fusionMailService->generateSubscriptionLetterAndSend($subscriber, $subscription, $node);
            } catch (\Exception $e) {
                $this->systemLogger->log($e->getMessage(), \LOG_ERR);
            }
        });
    }
}
