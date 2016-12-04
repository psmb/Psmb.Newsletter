<?php
namespace Psmb\Newsletter\Command;

use Psmb\Newsletter\Domain\Repository\SubscriberRepository;
use Psmb\Newsletter\Service\FusionMailService;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cli\CommandController;
use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Http\Response;
use TYPO3\Flow\Http\Uri;
use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\Flow\Mvc\Routing\UriBuilder;
use TYPO3\Flow\Mvc\Controller\Arguments;
use TYPO3\Flow\Mvc\Controller\ControllerContext;

/**
 * @Flow\Scope("singleton")
 */
class NewsletterCommandController extends CommandController
{
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
     * We can't do this in constructor as we need configuration to be injected
     */
    public function initializeObject() {
        $request = $this->createRequest();
        $controllerContext = $this->createControllerContext($request);
        $this->fusionMailService->setupObject($controllerContext, $request);
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
        array_map(function($letter) {
            $this->fusionMailService->sendLetter($letter);
        }, $letters);
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
            return $this->fusionMailService->generateSubscriptionLetter($subscriber, $subscription);
        }, $subscribers);
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
        return new ActionRequest($httpRequest);
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
}
