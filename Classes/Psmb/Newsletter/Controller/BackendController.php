<?php
namespace Psmb\Newsletter\Controller;

/*
 * This file is part of the Globit.Newsletter package.
 */

use Psmb\Newsletter\Domain\Model\Filter;
use Psmb\Newsletter\Domain\Model\Subscriber;
use Psmb\Newsletter\Domain\Repository\SubscriberRepository;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Error\Message;
use TYPO3\Flow\I18n\Translator;
use TYPO3\Flow\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\Flow\Persistence\QueryInterface;
use TYPO3\Media\Domain\Model\Document;
use TYPO3\Neos\Controller\Module\AbstractModuleController;

class BackendController extends AbstractModuleController
{
    /**
     * @Flow\Inject
     * @var SubscriberRepository
     */
    protected $subscriberRepository;

    /**
     * @Flow\InjectConfiguration(path="subscriptions", package="Psmb.Newsletter")
     * @var array
     */
    protected $subscriptions;

    /**
     * @Flow\Inject
     * @var Translator
     */
    protected $translator;

    /**
     * @param Filter $filter
     * @return void
     */
    public function indexAction($filter = NULL)
    {
        $this->subscriberRepository->setDefaultOrderings(['name' => QueryInterface::ORDER_ASCENDING]);
        $subscriptions = $filter ? $this->subscriberRepository->findAllByFilter($filter) : $this->subscriberRepository->findAll();

        $this->view->assign('subscribers', $subscriptions);
        $this->view->assign('filter', $filter);
        $this->view->assign('subscriptions', array_map(function ($subscription) {
            return $subscription['identifier'];
        }, $this->subscriptions));
    }

    /**
     * @param Subscriber $subscriber
     * @return void
     */
    public function editSubscriberAction($subscriber)
    {
        $this->view->assign('subscriber', $subscriber);
        $this->view->assign('subscriptions', array_map(function ($subscription) {
            return $subscription['identifier'];
        }, $this->subscriptions));
    }

    /**
     * @param Subscriber $subscriber
     * @return void
     */
    public function updateSubscriberAction($subscriber)
    {
        try {
            $this->subscriberRepository->update($subscriber);
            $this->addFlashMessage('The subscriber "%s" has been updated.', 'Remove Tag', Message::SEVERITY_OK, [$subscriber->getName()]);
        } catch (IllegalObjectTypeException $e) {
            $this->addFlashMessage('An Error occurred: %s', 'Error', Message::SEVERITY_ERROR, [$e->getMessage()]);
        }

        $this->redirect('editSubscriber', NULL, NULL, ['subscriber' => $subscriber]);
    }

    /**
     * @param Subscriber $subscriber
     * @return void
     */
    public function deleteSubscriberAction($subscriber)
    {
        try {
            $this->subscriberRepository->remove($subscriber);
            $this->addFlashMessage('The subscriber "%s" has been removed.', 'Remove Tag', Message::SEVERITY_OK, [$subscriber->getName()]);
        } catch (IllegalObjectTypeException $e) {
            $this->addFlashMessage('An Error occurred: %s', 'Error', Message::SEVERITY_ERROR, [$e->getMessage()]);
        }

        $this->redirect('index');
    }

    /**
     * @return void
     */
    public function newSubscriberAction()
    {
        $this->view->assign('subscriptions', array_map(function ($subscription) {
            return $subscription['identifier'];
        }, $this->subscriptions));
    }

    /**
     * @param Document $file
     * @return void
     */
    public function importSubscribersAction($file)
    {
        if ($file->getMediaType() != 'text/csv') {
            $this->addFlashMessage('Only csv is allowed.', 'Wrong file ending', Message::SEVERITY_WARNING);
            $this->redirect('index');
        }

        $filename = $_FILES['moduleArguments']['tmp_name']['file']['resource'];

        if (!is_readable($filename)) {
            $this->addFlashMessage('Sorry, but the file "%s" is not readable or does not exist...', 'File does not exist', Message::SEVERITY_WARNING, [$filename]);
            $this->redirect('index');
        }
        $handle = fopen($filename, "r");
        while (($line = fgetcsv($handle)) !== false) {
            if (count($line) === 3) {
                $email = $line[0];
                $name = $line[1] ?: "";
                $subscriptions = explode("|", $line[2]);

                if ($this->subscriberRepository->countByEmail($email) > 0) {
                    $message = $this->translator->translateById('flash.alreadyRegistered', [], null, null, 'Main', 'Psmb.Newsletter');
                    $this->addFlashMessage('%s: ' . $message, null, Message::SEVERITY_WARNING, [$email]);
                } else {
                    $subscriber = new Subscriber();
                    $subscriber->setEmail($email);
                    $subscriber->setName($name);
                    $subscriber->setSubscriptions($subscriptions);
                    $this->subscriberRepository->add($subscriber);
                }
            }
        }
        fclose($handle);

        $this->addFlashMessage('Subscribers have been added.', null, Message::SEVERITY_OK);
        $this->redirect('index');
    }

    /**
     * @param Subscriber $subscriber
     * @return void
     */
    public function createSubscriberAction($subscriber)
    {
        if ($this->subscriberRepository->countByEmail($subscriber->getEmail()) > 0) {
            $message = $this->translator->translateById('flash.alreadyRegistered', [], null, null, 'Main', 'Psmb.Newsletter');
            $this->addFlashMessage($message, null, Message::SEVERITY_WARNING);
        } else {
            try {
                $this->subscriberRepository->add($subscriber);
                $this->addFlashMessage('The subscriber "%s" has been added.', 'Remove Tag', Message::SEVERITY_OK, [$subscriber->getName()]);
            } catch (IllegalObjectTypeException $e) {
                $this->addFlashMessage('An Error occurred: %s', 'Error', Message::SEVERITY_ERROR, [$e->getMessage()]);
            }
        }

        $this->redirect('index');
    }
}
