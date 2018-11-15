<?php
namespace Psmb\Newsletter\Controller;

/*
 * This file is part of the Globit.Newsletter package.
 */

use League\Csv\Reader;
use League\Csv\Writer;
use Psmb\Newsletter\Domain\Model\Filter;
use Psmb\Newsletter\Domain\Model\Subscriber;
use Psmb\Newsletter\Domain\Repository\SubscriberRepository;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Error\Message;
use Neos\Flow\I18n\Translator;
use Neos\Flow\Persistence\Exception\IllegalObjectTypeException;
use Neos\Flow\Persistence\QueryInterface;
use Neos\Media\Domain\Model\Document;
use Neos\Neos\Controller\Module\AbstractModuleController;

class BackendController extends AbstractModuleController
{
    const STATUS_CREATED = 0, STATUS_UPDATED = 1, STATUS_CORRUPTED = 2;

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
            $this->addFlashMessage(
                $this->translator->translateById('flash.subscriber.updated', ["s" => $subscriber->getName()], null, null, 'Main', 'Psmb.Newsletter'),
                'Update Subscriber'
            );
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
            $this->addFlashMessage(
                $this->translator->translateById('flash.subscriber.removed', ["s" => $subscriber->getName()], null, null, 'Main', 'Psmb.Newsletter'),
                'Remove Subscriber'
            );
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
            $this->addFlashMessage(
                $this->translator->translateById('flash.csv', [], null, null, 'Main', 'Psmb.Newsletter'),
                'Wrong file ending',
                MESSAGE::SEVERITY_WARNING
            );
            $this->redirect('index');
        }

        $filename = $_FILES['moduleArguments']['tmp_name']['file']['resource'];

        if (!is_readable($filename)) {
            $this->addFlashMessage(
                $this->translator->translateById('flash.file.error', [], null, null, 'Main', 'Psmb.Newsletter'),
                'File error',
                MESSAGE::SEVERITY_ERROR
            );
            $this->redirect('index');
        }
        $csv = Reader::createFromPath($filename);
        $status[self::STATUS_CREATED] = 0;
        $status[self::STATUS_UPDATED] = 0;
        $status[self::STATUS_CORRUPTED] = 0;
        foreach ($line = $csv->getRecords() as $line) {
            if (count($line) === 3) {
                $email = $line[0];
                $name = $line[1] ?: "";
                $subscriptions = explode("|", $line[2]);

                if ($this->subscriberRepository->countByEmail($email) > 0) {
                    /** @var Subscriber $subscriber */
                    $subscriber = $this->subscriberRepository->findByEmail($email)->getFirst();
                    $subscriber->setSubscriptions($subscriptions);
                    $subscriber->setName($name);
                    $this->subscriberRepository->update($subscriber);
                    $status[self::STATUS_UPDATED]++;
                } else {
                    $subscriber = new Subscriber();
                    $subscriber->setEmail($email);
                    $subscriber->setName($name);
                    $subscriber->setSubscriptions($subscriptions);
                    $this->subscriberRepository->add($subscriber);
                    $status[self::STATUS_CREATED]++;
                }
            } else {
                $status[self::STATUS_CORRUPTED]++;
            }
        }

        $this->addFlashMessage(
            $this->translator->translateById('flash.file.error', $status, null, null, 'Main', 'Psmb.Newsletter'),
            'Remove Subscriber'
        );
        $this->redirect('index');
    }

    /**
     * @return void
     */
    public function exportSubscribersAction()
    {
        $csv = Writer::createFromFileObject(new \SplTempFileObject());

        /** @var Subscriber $subscriber */
        foreach ($this->subscriberRepository->findAll() as $subscriber) {
            $csv->insertOne([
                $subscriber->getEmail(),
                $subscriber->getName(),
                implode('|', $subscriber->getSubscriptions())
            ]);
        }

        $csv->output('subscriberlist.csv');
        die();
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
                $this->addFlashMessage(
                    $this->translator->translateById('flash.subscriber.added', ['s' => $subscriber->getName()], null, null, 'Main', 'Psmb.Newsletter'),
                    'Add successful'
                );
            } catch (IllegalObjectTypeException $e) {
                $this->addFlashMessage('An Error occurred: %s', 'Error', Message::SEVERITY_ERROR, [$e->getMessage()]);
            }
        }

        $this->redirect('index');
    }
}
