<?php
namespace Psmb\Newsletter\Service;

use TYPO3\Flow\Annotations as Flow;
use Psmb\Newsletter\Domain\Model\Subscriber;
use Psmb\Newsletter\View\FusionView;
use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\Flow\Mvc\Controller\ControllerContext;
use TYPO3\Flow\Mvc\Routing\UriBuilder;
use TYPO3\TYPO3CR\Domain\Model\Node;
use TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface;

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
     * @var Node
     */
    protected $siteNode;

    /**
     * @Flow\InjectConfiguration(path="globalSettings")
     * @var string
     */
    protected $globalSettings;

    public function initializeObject() {
        $this->siteNode = $this->getSiteNode();
    }

    /**
     * @param ControllerContext $controllerContext
     * @param ActionRequest $request
     */
    public function setupObject(ControllerContext $controllerContext, ActionRequest $request) {
        $this->view->setControllerContext($controllerContext);
        $this->uriBuilder->setRequest($request);
    }

    /**
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
     * Generate activation letter to confirm the new subscriber
     *
     * @param Subscriber $subscriber
     * @param string $hash
     * @return array
     */
    public function generateActivationLetter(Subscriber $subscriber, $hash)
    {
        $activationLink = $this->uriBuilder
            ->setCreateAbsoluteUri(TRUE)
            ->uriFor(
                'confirm',
                ['hash' => $hash],
                'Subscription',
                'Psmb.Newsletter'
            );

        $this->view->assign('value', [
            'site' => $this->siteNode,
            'documentNode' => $this->siteNode,
            'node' => $this->siteNode,
            'subscriber' => $subscriber,
            'globalSettings' => $this->globalSettings,
            'activationLink' => $activationLink
        ]);
        return $this->view->render();
    }

    /**
     * Genearate a letter for given subscriber and subscription
     *
     * @param Subscriber $subscriber
     * @param array $subscription
     * @return string
     */
    public function generateSubscriptionLetter(Subscriber $subscriber, $subscription)
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