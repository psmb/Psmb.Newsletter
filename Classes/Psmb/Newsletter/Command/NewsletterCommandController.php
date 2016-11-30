<?php
namespace Psmb\Newsletter\Command;

use Psmb\Newsletter\Domain\Repository\SubscriberRepository;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cli\CommandController;
use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Http\Response;
use TYPO3\Flow\Http\Uri;
use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\Flow\Mvc\Routing\UriBuilder;
use TYPO3\Flow\Mvc\Controller\Arguments;
use TYPO3\Flow\Mvc\Controller\ControllerContext;
use TYPO3\TYPO3CR\Domain\Model\Node;
use TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface;
use Psmb\Newsletter\View\TypoScriptView;

/**
 * @Flow\Scope("singleton")
 */
class NewsletterCommandController extends CommandController
{
    /**
     * @var ContextFactoryInterface
     */
    protected $contextFactory;

    /**
     * @var TypoScriptView
     */
    protected $view;

    /**
     * @var SubscriberRepository
     */
    protected $subscriberRepository;

    /**
     * @Flow\InjectConfiguration(path="globalSettings")
     * @var string
     */
    protected $globalSettings;

    /**
     * @Flow\InjectConfiguration(package="TYPO3.Flow", path="http.baseUri")
     * @var string
     */
    protected $baseUri;

    /**
     * @Flow\InjectConfiguration(path="subscriptions")
     * @var string
     */
    protected $subscriptions;

    /**
     * @var Node
     */
    protected $siteNode;

    /**
     * NewsletterCommandController constructor.
     *
     * @param TypoScriptView $view
     * @param ContextFactoryInterface $contextFactory
     * @param SubscriberRepository $subscriberRepository
     */
    public function __construct(TypoScriptView $view, ContextFactoryInterface $contextFactory, SubscriberRepository $subscriberRepository)
    {
        parent::__construct();
        $this->contextFactory = $contextFactory;
        $this->subscriberRepository = $subscriberRepository;
        $this->siteNode = $this->getSiteNode();
        $this->view = $view;
    }

    /**
     * We can't do this in constructor as we need configuration to be injected
     */
    public function initializeObject() {
        $controllerContext = $this->createControllerContext();
        $this->view->setControllerContext($controllerContext);
        $this->view->setTypoScriptPath('newsletter');
    }

    /**
     * Selects all subscriptions with given interval and sends a letter to each subscriber
     *
     * @param string $interval Select all subscriptions with this interval
     * @return string
     */
    public function sendCommand($interval)
    {
        $subscriptionsByInterval = array_filter($this->subscriptions, function ($item) use ($interval) {
            return $item['interval'] == $interval;
        });

        $nestedLetters = array_map([$this, 'generateLettersForSubscription'], $subscriptionsByInterval);
        $letters = array_reduce($nestedLetters, function ($acc, $item) {
            return array_merge($acc, $item);
        }, []);
        \TYPO3\Flow\var_dump($letters);
        //array_map([$this, 'sendLetter'], $letters);
    }

    /**
     * @param array $letter
     * @throws \Exception
     */
    protected function sendLetter($letter)
    {
        $subject = $letter['subject'];
        $body = $letter['body'];
        $recipientAddress = $letter['recipientAddress'];
        $recipientName = $letter['recipientName'];
        $senderAddress = $letter['senderAddress'];
        $senderName = $letter['senderName'];
        $replyToAddress = $letter['replyToAddress'];
        $carbonCopyAddress = $letter['carbonCopyAddress'];
        $blindCarbonCopyAddress = $letter['blindCarbonCopyAddress'];
        $format = $letter['format'];

        if (!$subject) {
            throw new \Exception('"subject" must be set.', 1327060321);
        }
        if (!$recipientAddress) {
            throw new \Exception('"recipientAddress" must be set.', 1327060201);
        }
        if (!$senderAddress) {
            throw new \Exception('"senderAddress" must be set.', 1327060211);
        }

        $mail = new \TYPO3\SwiftMailer\Message();
        $mail
            ->setFrom(array($senderAddress => $senderName))
            ->setTo(array($recipientAddress => $recipientName))
            ->setSubject($subject);
        if ($replyToAddress) {
            $mail->setReplyTo($replyToAddress);
        }
        if ($carbonCopyAddress) {
            $mail->setCc($carbonCopyAddress);
        }
        if ($blindCarbonCopyAddress) {
            $mail->setBcc($blindCarbonCopyAddress);
        }
        if ($format === 'plaintext') {
            $mail->setBody($body, 'text/plain');
        } else {
            $mail->setBody($body, 'text/html');
        }
        $mail->send();
    }

    /**
     * Generate a letter for each subscriber in the subscription
     *
     * @param array $subscription
     * @return array Array of letters
     */
    protected function generateLettersForSubscription($subscription)
    {
        $subscribers = $this->subscriberRepository->findBySubscriptionId($subscription['identifier'])->toArray();
        return array_map(function ($subscriber) use ($subscription) {
            return $this->generateLetter($subscriber, $subscription);
        }, $subscribers);
    }

    /**
     * Render a Fusion view to generate a letter array for the give subscriber and subscription
     *
     * @param $subscriber
     * @param $subscription
     * @return array
     */
    protected function generateLetter($subscriber, $subscription)
    {
        $this->view->assign('value', [
            'site' => $this->siteNode,
            'documentNode' => $this->siteNode,
            'node' => $this->siteNode,
            'subscriber' => $subscriber,
            'subscription' => $subscription,
            'globalSettings' => $this->globalSettings
        ]);
        return $this->view->render();
    }

    /**
     * Creates a controller content context for live dimension
     *
     * @return ControllerContext
     */
    protected function createControllerContext()
    {
        $httpRequest = Request::createFromEnvironment();
        if ($this->baseUri) {
            $baseUri = new Uri($this->baseUri);
            $httpRequest->setBaseUri($baseUri);
        }
        $request = new ActionRequest($httpRequest);
        $uriBuilder = new UriBuilder();
        $uriBuilder->setRequest($request);
        $controllerContext = new ControllerContext(
            $request,
            new Response(),
            new Arguments([]),
            $uriBuilder
        );
        return $controllerContext;
    }

    /**
     * @return Node
     */
    protected function getSiteNode()
    {
        $contextProperties = array(
            'workspaceName' => 'live',
            'dimensions' => [],
            'invisibleContentShown' => false,
            'inaccessibleContentShown' => false
        );
        $context = $this->contextFactory->create($contextProperties);
        return $context->getCurrentSiteNode();
    }
}
