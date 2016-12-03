<?php
namespace Psmb\Newsletter\Controller;

use Psmb\Newsletter\Domain\Model\Subscriber;
use Psmb\Newsletter\Domain\Repository\SubscriberRepository;
use TYPO3\Flow\Mvc\Controller\ActionController;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Mvc\View\JsonView;
use TYPO3\Fluid\View\TemplateView;

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
        $this->subscriberRepository->add($newSubscriber);
        $this->redirect('edit', null, null, ['subscriber' => $newSubscriber]);
        // $this->view->assign('value', array('success' => true));
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
}
