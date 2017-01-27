<?php
namespace Psmb\Newsletter\Command;

use TYPO3\Flow\Annotations as Flow;
use Psmb\Newsletter\Domain\Model\Subscriber;
use Psmb\Newsletter\Domain\Repository\SubscriberRepository;
use Psmb\Newsletter\Service\FusionMailService;
use TYPO3\Flow\Cli\CommandController;

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
     * @var array
     */
    protected $subscriptions;

    /**
     * Import newsletter subscribers from CSV.
     * CSV should be in the format `"user@email.com","User Name","subscriptionId1|subscriptionId2"`
     *
     * @param string $filename
     * @return void
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
     * @return void
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

        array_walk($subscriptions, function ($subscription) use ($dryRun) {
            $this->sendLettersForSubscription($subscription, $dryRun);
        });
    }

    /**
     * Generate a letter for each subscriber in the subscription
     *
     * @param array $subscription
     * @param bool $dryRun
     * @return void
     */
    protected function sendLettersForSubscription($subscription, $dryRun)
    {
        $subscribers = $this->subscriberRepository->findBySubscriptionId($subscription['identifier'])->toArray();

        $this->outputLine('Sending letters for subscription %s (%s subscribers)', [$subscription['identifier'], count($subscribers)]);
        $this->outputLine('-------------------------------------------------------------------------------');

        array_walk($subscribers, function ($subscriber) use ($subscription, $dryRun) {
            $this->outputLine('Sending a letter for %s', [$subscriber->getEmail()]);
            if ($dryRun) {
                $letter = $this->fusionMailService->generateSubscriptionLetter($subscriber, $subscription);
                $this->outputLine(print_r($letter, true));
            } else {
                $this->fusionMailService->generateSubscriptionLetterAndSend($subscriber, $subscription);
            }
        });
    }

}
