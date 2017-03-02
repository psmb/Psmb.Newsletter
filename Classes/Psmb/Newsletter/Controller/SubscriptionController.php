<?php
namespace Psmb\Newsletter\Controller;

use Psmb\Newsletter\Domain\Model\Subscriber;
use Psmb\Newsletter\Domain\Repository\SubscriberRepository;
use Psmb\Newsletter\Service\FusionMailService;
use Neos\Error\Messages\Message;
use Neos\Flow\I18n\Service as I18nService;
use Neos\Flow\I18n\Translator;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Utility\Algorithms;
use Neos\Flow\Validation\Validator\EmailAddressValidator;

class SubscriptionController extends ActionController
{
    /**
     * @Flow\Inject
     * @var I18nService
     */
    protected $i18nService;

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
     * @var \Neos\Cache\Frontend\VariableFrontend
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
        $additionalData = $this->request->getInternalArgument('__additionalData');
        $this->view->assign('additionalData', $additionalData);
        $this->view->assign('subscriptions', $this->subscriptions);
        $this->view->assign('currentLocale', $this->i18nService->getConfiguration()->getCurrentLocale());
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
                $this->fusionMailService->sendActivationLetter($subscriber, $hash);
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
        $this->view->assign('currentLocale', $this->i18nService->getConfiguration()->getCurrentLocale());
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

}
