<?php
namespace Psmb\Newsletter\Controller;

use Psmb\Newsletter\Domain\Model\Subscriber;
use Psmb\Newsletter\Domain\Repository\SubscriberRepository;
use TYPO3\Flow\Mvc\Controller\ActionController;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Mvc\View\JsonView;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;
use TYPO3\Flow\Utility\Algorithms;
use TYPO3\Fluid\View\TemplateView;
use TYPO3\SwiftMailer\Message;

class SubscriptionController extends ActionController
{
    /**
     * @var array
     */
    protected $viewFormatToObjectNameMap = array(
        'html' => TemplateView::class,
        'json' => JsonView::class
    );

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
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\Mvc\Routing\UriBuilder
     */
    protected $uriBuilder;

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
        $email = $newSubscriber->getEmail();
        $this->tokenCache->set(
            $hash,
            $newSubscriber
        );
        $this->sendActivationMail($email, $hash);
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
        $this->redirect('edit', null, null, ['subscriber' => $subscriber]);
    }

    /**
     * Sends an activation mail
     *
     * @param string $recipientAddress
     * @param string $hash
     * @return int
     */
    protected function sendActivationMail($recipientAddress, $hash) {
        $this->uriBuilder->setRequest($this->request);
        $activationLink = $this->uriBuilder
            ->setCreateAbsoluteUri(TRUE)
            ->uriFor(
                'confirm',
                ['hash' => $hash],
                'Subscription',
                'Psmb.Newsletter'
            );
        $mail = new Message();
        $mail->setFrom(['np-reply@psmb.ru' => 'Your Robot'])
            ->setTo($recipientAddress)
            ->setSubject('Activate your account');
        $mail->setBody($activationLink, 'text/plain');
        return $mail->send();
    }

}
