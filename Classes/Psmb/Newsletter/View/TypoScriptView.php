<?php
namespace Psmb\Newsletter\View;

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\I18n\Locale;
use TYPO3\Flow\I18n\Service;
use TYPO3\Flow\Mvc\View\AbstractView;
use TYPO3\Neos\Domain\Service\TypoScriptService;
use TYPO3\Neos\Exception;
use TYPO3\TYPO3CR\Domain\Model\Node;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TypoScript\Core\Runtime;
use TYPO3\TypoScript\Exception\RuntimeException;
use TYPO3\Flow\Security\Context;

/**
 * A TypoScript view
 */
class TypoScriptView extends AbstractView
{
	/**
	 * @Flow\Inject
	 * @var Service
	 */
	protected $i18nService;

	/**
	 * This contains the supported options, their default values, descriptions and types.
	 *
	 * @var array
	 */
	protected $supportedOptions = array(
		'enableContentCache' => array(null, 'Flag to enable content caching inside TypoScript (overriding the global setting).', 'boolean')
	);

	/**
	 * @Flow\Inject
	 * @var TypoScriptService
	 */
	protected $typoScriptService;

	/**
	 * The TypoScript path to use for rendering the node given in "value", defaults to "page".
	 *
	 * @var string
	 */
	protected $typoScriptPath = 'newsletter';

	/**
	 * @var Runtime
	 */
	protected $typoScriptRuntime;

	/**
	 * @Flow\Inject
	 * @var Context
	 */
	protected $securityContext;

	/**
	 * Renders the view
	 *
	 * @return string The rendered view
	 * @throws \Exception if no node is given
	 * @api
	 */
	public function render()
	{
		$contextVars = $this->variables['value'];
		if (!is_array($contextVars)) {
			throw new Exception('TypoScriptView needs an array for variable \'value\'.', 1329736457);
		}
		$siteNode = $contextVars['site'];
		if (!$siteNode instanceof Node) {
			throw new Exception('TypoScriptView needs a site node to be set in context variables passed to \'value\'.', 1329736457);
		}
		$typoScriptRuntime = $this->getTypoScriptRuntime($siteNode);

		$dimensions = $siteNode->getContext()->getDimensions();
		if (array_key_exists('language', $dimensions) && $dimensions['language'] !== array()) {
			$currentLocale = new Locale($dimensions['language'][0]);
			$this->i18nService->getConfiguration()->setCurrentLocale($currentLocale);
			$this->i18nService->getConfiguration()->setFallbackRule(array('strict' => false, 'order' => array_reverse($dimensions['language'])));
		}

		$typoScriptRuntime->pushContextArray($contextVars);
		try {
			$output = $typoScriptRuntime->render($this->typoScriptPath);
		} catch (RuntimeException $exception) {
			throw $exception->getPrevious();
		}
		$typoScriptRuntime->popContext();

		return $output;
	}

	/**
	 * Set the TypoScript path to use for rendering the node given in "value"
	 *
	 * @param string $typoScriptPath
	 * @return void
	 */
	public function setTypoScriptPath($typoScriptPath)
	{
		$this->typoScriptPath = $typoScriptPath;
	}

	/**
	 * @return string
	 */
	public function getTypoScriptPath()
	{
		return $this->typoScriptPath;
	}

	/**
	 * @param NodeInterface $siteNode
	 * @return \TYPO3\TypoScript\Core\Runtime
	 */
	protected function getTypoScriptRuntime(NodeInterface $siteNode)
	{
		if ($this->typoScriptRuntime === null) {
			$this->typoScriptRuntime = $this->typoScriptService->createRuntime($siteNode, $this->controllerContext);

			if (isset($this->options['enableContentCache']) && $this->options['enableContentCache'] !== null) {
				$this->typoScriptRuntime->setEnableContentCache($this->options['enableContentCache']);
			}
		}
		return $this->typoScriptRuntime;
	}

	/**
	 * Clear the cached runtime instance on assignment of variables
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return TypoScriptView
	 */
	public function assign($key, $value)
	{
		$this->typoScriptRuntime = null;
		return parent::assign($key, $value);
	}
}
