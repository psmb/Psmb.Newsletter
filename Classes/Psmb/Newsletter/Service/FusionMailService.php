<?php
namespace Psmb\Newsletter\Service;

use TYPO3\Flow\Annotations as Flow;
use Flowpack\JobQueue\Common\Annotations as Job;
use Psmb\Newsletter\Domain\Model\Subscriber;
use Psmb\Newsletter\View\FusionView;
use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\Flow\Mvc\Controller\ControllerContext;
use TYPO3\Flow\Mvc\Routing\UriBuilder;
use TYPO3\SwiftMailer\Message;
use TYPO3\TYPO3CR\Domain\Model\Node;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface;
use Psmb\Newsletter\Domain\Repository\SubscriberRepository;
use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Http\Response;
use TYPO3\Flow\Http\Uri;
use TYPO3\Flow\Mvc\Controller\Arguments;


/**
 * @Flow\Scope("singleton")
 */
class FusionMailService {

    /**
     * @Flow\Inject
     * @var UriBuilder
     */
    protected $uriBuilder;

    /**
     * @Flow\Inject
     * @var ContextFactoryInterface
     */
    protected $contextFactory;

    /**
     * @Flow\Inject
     * @var FusionView
     */
    protected $view;

    /**
     * @Flow\InjectConfiguration(path="globalSettings")
     * @var string
     */
    protected $globalSettings;

    /**
     * @Flow\Inject
     * @var SubscriberRepository
     */
    protected $subscriberRepository;

    /**
     * @Flow\InjectConfiguration(package="TYPO3.Flow", path="http.baseUri")
     * @var string
     */
    protected $baseUri;

    /**
     * @Flow\InjectConfiguration(path="subscriptions")
     * @var array
     */
    protected $subscriptions;

    /**
     * We can't do this in constructor as we need configuration to be injected
     */
    public function initializeObject() {
        $request = $this->createRequest();
        $controllerContext = $this->createControllerContext($request);
        $this->view->setControllerContext($controllerContext);
        $this->uriBuilder->setRequest($request);
    }

    /**
     * @return ActionRequest
     */
    protected function createRequest() {
        $_SERVER['FLOW_REWRITEURLS'] = 1;
        $httpRequest = Request::createFromEnvironment();
        if ($this->baseUri) {
            $baseUri = new Uri($this->baseUri);
            $httpRequest->setBaseUri($baseUri);
        }
        $request = new ActionRequest($httpRequest);
        $request->setFormat('html');
        return $request;
    }

    /**
     * Creates a controller content context for live dimension
     *
     * @param ActionRequest $request
     * @return ControllerContext
     */
    protected function createControllerContext($request)
    {
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
     * Just a simple wrapper over SwiftMailer
     *
     * @param array $letter
     * @throws \Exception
     */
    public function sendLetter($letter)
    {
        $subject = isset($letter['subject']) ? $letter['subject'] : null;
        $body = isset($letter['body']) ? $letter['body'] : null;
        $recipientAddress = isset($letter['recipientAddress']) ? $letter['recipientAddress'] : null;
        $recipientName = isset($letter['recipientName']) ? $letter['recipientName'] : null;
        $senderAddress = isset($letter['senderAddress']) ? $letter['senderAddress'] : null;
        $senderName = isset($letter['senderName']) ? $letter['senderName'] : null;
        $replyToAddress = isset($letter['replyToAddress']) ? $letter['replyToAddress'] : null;
        $carbonCopyAddress = isset($letter['carbonCopyAddress']) ? $letter['carbonCopyAddress'] : null;
        $blindCarbonCopyAddress = isset($letter['blindCarbonCopyAddress']) ? $letter['blindCarbonCopyAddress'] : null;
        $format = isset($letter['format']) ? $letter['format'] : null;

        if (!$subject) {
            throw new \Exception('"subject" must be set.', 1327060321);
        }
        if (!$recipientAddress) {
            throw new \Exception('"recipientAddress" must be set.', 1327060201);
        }
        if (!$senderAddress) {
            throw new \Exception('"senderAddress" must be set.', 1327060211);
        }

        $mail = new Message();
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
     * Generate activation letter to confirm the new subscriber
     *
     * @param Subscriber $subscriber
     * @param string $hash
     * @return array
     */
    public function generateActivationLetter(Subscriber $subscriber, $hash)
    {
        $metadata = $subscriber->getMetadata();
        $siteNode = $this->getSiteNode($metadata['registrationDimensions']);
        $activationLink = $this->uriBuilder
            ->setCreateAbsoluteUri(TRUE)
            ->uriFor(
                'confirm',
                ['hash' => $hash],
                'Subscription',
                'Psmb.Newsletter'
            );

        $this->view->assign('value', [
            'site' => $siteNode,
            'documentNode' => $siteNode,
            'node' => $siteNode,
            'subscriber' => $subscriber,
            'globalSettings' => $this->globalSettings,
            'activationLink' => $activationLink
        ]);
        return $this->view->render();
    }

    /**
     * Generate a letter for given subscriber and subscription
     *
     * @param Subscriber $subscriber
     * @param array $subscription
     * @param null|NodeInterface $node
     * @return array
     */
    public function generateSubscriptionLetter(Subscriber $subscriber, $subscription, $node = NULL)
    {
        $dimensions = isset($subscription['dimensions']) ? $subscription['dimensions'] : null;
        $siteNode = $this->getSiteNode($dimensions);
        $node = $node ?: $siteNode;
        $this->view->assign('value', [
            'site' => $siteNode,
            'documentNode' => $node,
            'node' => $node,
            'subscriber' => $subscriber,
            'subscription' => $subscription,
            'globalSettings' => $this->globalSettings
        ]);
        return $this->view->render();
    }

    /**
     * Generate a letter for given subscriber and subscription and sends it. Async.
     *
     * @Job\Defer(queueName="psmb-newsletter")
     * @param Subscriber $subscriber
     * @param array $subscription
     * @param null|NodeInterface $node
     * @return void
     */
    public function generateSubscriptionLetterAndSend(Subscriber $subscriber, $subscription, $node = NULL)
    {
        $letter = $this->generateSubscriptionLetter($subscriber, $subscription, $node);
        if ($letter) {
            $this->sendLetter($letter);
        }
    }

    /**
     * @param array $dimensions
     * @return Node
     */
    protected function getSiteNode($dimensions = [])
    {
        $contextProperties = array(
            'workspaceName' => 'live',
            'dimensions' => $dimensions,
            'invisibleContentShown' => false,
            'inaccessibleContentShown' => false
        );
        $context = $this->contextFactory->create($contextProperties);
        return $context->getCurrentSiteNode();
    }

}