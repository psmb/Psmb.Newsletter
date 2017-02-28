<?php
namespace Psmb\Newsletter\Controller\Module;

use Psmb\Newsletter\Domain\Model\Subscriber;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Configuration\ConfigurationManager;
use TYPO3\Flow\Configuration\Source\YamlSource;
use TYPO3\Flow\Error\Message;
use TYPO3\Flow\Http\Client\CurlEngineException;
use TYPO3\Flow\Package\PackageManagerInterface;
use TYPO3\Flow\Utility\Arrays;
use TYPO3\Neos\Controller\Module\AbstractModuleController;
use Psmb\Newsletter\Domain\Repository\SubscriberRepository;

/**
 * Class SubscriberController
 * @package RFY\BKWI\Controller\Module
 */
class SubscriberController extends AbstractModuleController
{

    /**
     * @Flow\Inject
     * @var ConfigurationManager
     */
    protected $configurationManager;

    /**
     * @Flow\Inject
     * @var YamlSource
     */
    protected $configurationSource;

    /**
     * @Flow\Inject
     * @var PackageManagerInterface
     */
    protected $packageManager;

    /**
     * @Flow\Inject
     * @var SubscriberRepository
     */
    protected $subscriberRepository;

    /**
     * @Flow\InjectConfiguration(package="Psmb.Newsletter", path="subscriptions")
     * @var string
     */
    protected $subscriptions;

    /**
     * An edit view for the global Piwik settings and
     * Management Module for Piwik Sites through Piwik API
     *
     * @return void
     */
    public function indexAction()
    {
        $subscribers = $this->subscriberRepository->findAll();

        $this->view->assign('subscribers', $subscribers);
    }

    /**
     * An edit view for a subscriber
     *
     * @return void
     */
    public function newAction()
    {
        $this->view->assign('subscriptions', $this->subscriptions);
    }

    public function createAction(Subscriber $subscriber)
    {
        $this->subscriberRepository->add($subscriber);
        $this->redirect('index');
    }


    /**
     * An edit view for a subscriber
     *
     * @param Subscriber $subscriber
     * @return void
     */
    public function editAction(Subscriber $subscriber)
    {
        $this->view->assign('subscriber', $subscriber);
        $this->view->assign('subscriptions', $this->subscriptions);
    }

    /**
     * Update Subscriber
     *
     * @param Subscriber $subscriber
     * @return void
     */
    public function updateAction(Subscriber $subscriber)
    {
        $this->subscriberRepository->update($subscriber);
        $this->redirect('index');
    }

    /**
     * Update Subscriber
     *
     * @param Subscriber $subscriber
     * @return void
     */
    public function deleteAction(Subscriber $subscriber)
    {
        $this->subscriberRepository->remove($subscriber);
        $this->persistenceManager->persistAll();
        $this->redirect('index');
    }
}