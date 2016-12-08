<?php
namespace Psmb\Newsletter\Controller;

use Psmb\Newsletter\Domain\Model\Subscriber;
use Psmb\Newsletter\Domain\Repository\SubscriberRepository;
use Psmb\Newsletter\Service\FusionMailService;
use TYPO3\Flow\Error\Message;
use TYPO3\Flow\I18n\Translator;
use TYPO3\Flow\Mvc\Controller\ActionController;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Utility\Algorithms;
use TYPO3\Flow\Validation\Validator\EmailAddressValidator;

class SubscriptionController extends ActionController
{
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
     * @param Subscriber $subscriber
     * @return void
     */
    public function registerAction(Subscriber $subscriber)
    {

        $email = $subscriber->getEmail();
        if (!$email) {
            $message = $this->translator->translateById('flash.noEmail', [], null, null, 'Main', 'Psmb.Newsletter');
            $this->addFlashMessage($message, null, Message::SEVERITY_WARNING);
            $this->redirect('index');
        } else {
            $emailValidator = new EmailAddressValidator();
            $validationResult = $emailValidator->validate($email);
            if ($validationResult->hasErrors()) {
                $message = $validationResult->getFirstError()->getMessage();
                $this->addFlashMessage($message, null, Message::SEVERITY_WARNING);
                $this->redirect('index');
            } elseif ($this->subscriberRepository->countByEmail($email) > 0) {
                $message = $this->translator->translateById('flash.alreadyRegistered', [], null, null, 'Main', 'Psmb.Newsletter');
                $this->addFlashMessage($message, null, Message::SEVERITY_WARNING);
                $this->redirect('index');
            } else {
                $subscriber->setMetadata([
                    'registrationDate' => new \DateTime(),
                    'registrationDimensions' => $this->request->getInternalArgument('__node')->getContext()->getDimensions()
                ]);
                $hash = Algorithms::generateRandomToken(16);
                $this->tokenCache->set(
                    $hash,
                    $subscriber
                );
                $message = $this->translator->translateById('flash.confirm', [], null, null, 'Main', 'Psmb.Newsletter');
                $this->addFlashMessage($message);
                $this->sendActivationLetter($subscriber, $hash);
                $this->redirect('feedback');
            }
        }
    }

    /**
     * Create new Subscription based on the hash and delete the hash
     *
     * @param string $hash
     */
    public function confirmAction($hash)
    {
        $subscriber = $this->tokenCache->get($hash);
        if ($subscriber) {
            $this->tokenCache->remove($hash);
            $this->subscriberRepository->add($subscriber);
            $this->persistenceManager->persistAll();
            $message = $this->translator->translateById('flash.confirmed', [], null, null, 'Main', 'Psmb.Newsletter');
            $this->addFlashMessage($message);
        } else {
            $message = $this->translator->translateById('flash.noToken', [], null, null, 'Main', 'Psmb.Newsletter');
            $this->addFlashMessage($message, null, Message::SEVERITY_WARNING);
        }
        $this->redirect('feedback');
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
     * @throws \Exception
     * @return void
     */
    public function updateAction(Subscriber $subscriber)
    {
        $this->subscriberRepository->update($subscriber);
        $message = $this->translator->translateById('flash.updated', [], null, null, 'Main', 'Psmb.Newsletter');
        $this->addFlashMessage($message);
        $this->redirect('feedback');
    }

    /**
     * Deletes a Subscriber
     *
     * @param Subscriber $subscriber
     */
    public function unsubscribeAction(Subscriber $subscriber)
    {
        $this->subscriberRepository->remove($subscriber);
        $this->persistenceManager->persistAll();
        $message = $this->translator->translateById('flash.unsubscribed', [], null, null, 'Main', 'Psmb.Newsletter');
        $this->addFlashMessage($message);
        $this->redirect('feedback');
    }

    /**
     * Just render flash messages
     */
    public function feedbackAction()
    {
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
