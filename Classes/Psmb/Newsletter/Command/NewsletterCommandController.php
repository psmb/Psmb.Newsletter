<?php
namespace Psmb\Newsletter\Command;

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cli\CommandController;
use TYPO3\Flow\Cli\ConsoleOutput;
use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Http\Response;
use TYPO3\Flow\Mvc\Routing\UriBuilder;
use TYPO3\Flow\Mvc\Controller\Arguments;
use TYPO3\Flow\Mvc\Controller\ControllerContext;
use TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface;
use Psmb\Newsletter\View\TypoScriptView;

/**
 * @Flow\Scope("singleton")
 */
class NewsletterCommandController extends CommandController
{
	/**
	 * @Flow\Inject
	 * @var ContextFactoryInterface
	 */
	protected $contextFactory;

	/**
	 * @Flow\Inject
	 * @var TypoScriptView
	 */
	protected $view;

	/**
	 * @var ConsoleOutput
	 */
	protected $output;

	/**
 * Send newsletter
 *
 * @return string
 */
	public function sendCommand()
	{
		$contextProperties = array(
			'workspaceName' => 'live',
			'dimensions' => [],
			'invisibleContentShown' => false,
			'inaccessibleContentShown' => false
		);
		$controllerContext = $this->createControllerContext();

		$context = $this->contextFactory->create($contextProperties);
		$siteNode = $context->getCurrentSiteNode();

		$this->view->setTypoScriptPath('newsletter');
		$this->view->setControllerContext($controllerContext);
		$this->view->assign('value', array(
			'site' => $siteNode,
			'user' => array('name' => 'Dmitri Pisarev', 'email' => 'dimaip@gmail.com')
		));
		echo $this->view->render();
	}

	/**
	 * Creates a controller content context for live dimension
	 *
	 * @return ControllerContext
	 */
	protected function createControllerContext()
	{
		$httpRequest = Request::createFromEnvironment();
		$request = $httpRequest->createActionRequest();
		$uriBuilder = new UriBuilder();
		$uriBuilder->setRequest($request);
		$controllerContext = new ControllerContext(
			$request,
			new Response(),
			new Arguments([]),
			$uriBuilder
		);

		return $controllerContext;
	}
}
