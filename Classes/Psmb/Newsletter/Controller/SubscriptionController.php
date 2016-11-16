<?php
namespace Psmb\Newsletter\Controller;

use Psmb\Newsletter\Domain\Model\Subscriber;
use Psmb\Newsletter\Domain\Repository\SubscriberRepository;
use TYPO3\Flow\Mvc\Controller\ActionController;
use TYPO3\Flow\Annotations as Flow;

class SubscriptionController extends ActionController
{
    /**
     * @var string
     */
    protected $defaultViewObjectName = 'TYPO3\Flow\Mvc\View\JsonView';

    /**
     * @Flow\Inject
     * @var SubscriberRepository
     */
    protected $subscriberRepository;

    /**
     * Registers a new subscriber
     *
     * @param Subscriber $newSubscriber
     * @Flow\Validate(argumentName="$newSubscriber", type="UniqueEntity")
     * @return void
     */
    public function registerAction(Subscriber $newSubscriber)
    {
        $this->subscriberRepository->add($newSubscriber);
        $this->addFlashMessage('Registered a new subscriber.');
        $this->view->assign('value', array('success' => true));
    }
}
