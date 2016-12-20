<?php
namespace Psmb\Newsletter\Command;

use Psmb\Newsletter\Domain\Model\Subscriber;
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
use TYPO3\Flow\Persistence\PersistenceManagerInterface;

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
     * Import newsletter subscribers from CSV.
     * CSV should be in the format `"user@email.com","User Name","subscriptionId1|sibscriptionId2"`
     *
     * @param string $filename
     * @return string
     */
    public function importCsvCommand($filename)
    {
        if (!is_readable($filename)) {
            $this->outputLine('<error>Sorry, but the file "%s" is not readable or does not exist...</error>', [$filename]);
            $this->outputLine();
            $this->sendAndExit(1);
        }
        $handle = fopen($filename, "r");
        while (($line = fgetcsv($handle)) !== false) {
            if (count($line) === 3) {
                $email = $line[0];
                $name = $line[1] ?: "";
                $subscriptions = explode("|", $line[2]);

                if ($this->subscriberRepository->countByEmail($email) > 0) {
                    $this->outputLine('<error>User with email "%s" already exists, skipping...</error>', [$email]);
                } else {
                    $this->outputLine('Creating a subscriber (%s, %s, %s)', [$email, $name, $line[2]]);
                    $subscriber = new Subscriber();
                    $subscriber->setEmail($email);
                    $subscriber->setName($name);
                    $subscriber->setSubscriptions($subscriptions);
                    $this->subscriberRepository->add($subscriber);
                }
            }
        }
        fclose($handle);
    }

    /**
     * Selects all subscriptions with given interval and sends a letter to each subscriber
     *
     * @param string $subscription Subscription id to send newsletter to
     * @param string $interval Alternatively select all subscriptions with the given interval (useful for cron jobs)
     * @param bool $dryRun DryRun: generate messages but don't send
     * @return string
     */
    public function sendCommand($subscription = null, $interval = null, $dryRun = null)
    {
        $subscriptions = [];
        if ($subscription) {
            $subscriptions = array_filter($this->subscriptions, function ($item) use ($subscription) {
                return $item['identifier'] == $subscription;
            });
        } else if ($interval) {
            $subscriptions = array_filter($this->subscriptions, function ($item) use ($interval) {
                return $item['interval'] == $interval;
            });
        } else {
            $this->outputLine('<error>Either an interval or a subscription must be set</error>');
            $this->outputLine();
            $this->sendAndExit(1);
        }

        $nestedLetters = array_map([$this, 'generateLettersForSubscription'], $subscriptions);
        $letters = array_reduce($nestedLetters, function ($acc, $item) {
            return array_merge($acc, $item);
        }, []);

        if ($dryRun) {
            $this->outputLine(print_r($letters, 1));
        } else {
            array_map(function($letter) {
              $this->fusionMailService->sendLetter($letter);
            }, $letters);
        }
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

        $this->outputLine('Sending letters for subscription %s (%s subscribers)', [$subscription['identifier'], count($subscribers)]);
        $this->outputLine('-------------------------------------------------------------------------------');
        $letters = array_map(function ($subscriber) use ($subscription) {
            $this->outputLine('Sending a letter for %s', [$subscriber->getEmail()]);
            $letter = $this->fusionMailService->generateSubscriptionLetter($subscriber, $subscription);
            if (!$letter) {
                $this->outputLine('<error>Nothing to send</error>');
            }
            return $letter;
        }, $subscribers);
        return array_filter($letters);
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
