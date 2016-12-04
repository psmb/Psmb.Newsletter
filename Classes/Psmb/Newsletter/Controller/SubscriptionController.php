<?php
namespace Psmb\Newsletter\Controller;

use Psmb\Newsletter\Domain\Model\Subscriber;
use Psmb\Newsletter\Domain\Repository\SubscriberRepository;
use Psmb\Newsletter\Service\FusionMailService;
use TYPO3\Flow\Mvc\Controller\ActionController;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Utility\Algorithms;

class SubscriptionController extends ActionController
{
    /**
     * @Flow\Inject
     * @var FusionMailService
     */
    protected $fusionMailService;

    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\Cache\Frontend\VariableFrontend
     */
    protected $tokenCache;

    /**
     * @Flow\Inject
     * @var SubscriberRepository
     */
    protected $subscriberRepository;

    /**
    * @Flow\InjectConfiguration(path="subscriptions")
    * @var string
    */
    protected $subscriptions;

    /**
     * Render a form
     *
     * @return void
     */
    public function indexAction()
    {
        $this->view->assign('subscriptions', $this->subscriptions);
    }

    /**
     * Registers a new subscriber
     *
     * @param Subscriber $newSubscriber
     * @Flow\Validate(argumentName="$newSubscriber", type="UniqueEntity")
     * @return void
     */
    public function registerAction(Subscriber $newSubscriber)
    {
        $hash = Algorithms::generateRandomToken(16);
        $this->tokenCache->set(
            $hash,
            $newSubscriber
        );
        $this->sendActivationLetter($newSubscriber, $hash);
        $this->addFlashMessage('Please confirm your subscription');
        $this->redirect('index');
    }

    /**
     * Create new Subscription based on the hash and delete the hash
     *
     * @param string $hash
     */
    public function confirmAction($hash)
    {
        $newSubscriber = $this->tokenCache->get($hash);
        if ($newSubscriber) {
            $this->tokenCache->remove($hash);
            $this->subscriberRepository->add($newSubscriber);
            $this->persistenceManager->persistAll();
            $this->addFlashMessage('Subscirption has been confirmed');
        } else {
            $this->addFlashMessage('No token provided', 'Something is wrong', \TYPO3\Flow\Error\Message::SEVERITY_ERROR);
        }
        $this->redirect('index');
    }

    /**
     * Render an edit form
     *
     * @param Subscriber $subscriber
     * @throws \Exception
     * @return void
     */
    public function editAction(Subscriber $subscriber)
    {
        $this->view->assign('subscriber', $subscriber);
        $this->view->assign('subscriptions', $this->subscriptions);
    }

    /**
     * Updates a subscriber
     *
     * @param Subscriber $subscriber
     * @Flow\Validate(argumentName="$subscriber", type="UniqueEntity")
     * @throws \Exception
     * @return void
     */
    public function updateAction(Subscriber $subscriber)
    {
        $this->subscriberRepository->update($subscriber);
        $this->addFlashMessage('Subscription updated');
        $this->redirect('edit', null, null, ['subscriber' => $subscriber]);
    }

    /**
     * Sends an activation mail
     *
     * @param Subscriber $subscriber
     * @param string $hash
     * @return int
     */
    protected function sendActivationLetter(Subscriber $subscriber, $hash) {
        $this->fusionMailService->setupObject($this->controllerContext, $this->request);
        $activationLetter = $this->fusionMailService->generateActivationLetter($subscriber, $hash);
        $this->fusionMailService->sendLetter($activationLetter);
    }

}
