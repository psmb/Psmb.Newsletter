<?php
namespace Psmb\Newsletter\Fusion;

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\FusionObjects\AbstractFusionObject;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;


use Psr\Log\LoggerInterface;

/**
 * A Fusion Object that inlines styles
 */
class CssToInlineImplementation extends AbstractFusionObject {
    /**
     * `value` should contain html to be processed
     * `cssPath` should contain a path to CSS file, e.g. `cssPath = 'resource://Psmb.Newsletter/Public/styles.css'`
     * @return string
     */
    public function evaluate() {
        $html = $this->fusionValue('value');
        $cssFile = $this->fusionValue('cssPath');
        $css = file_get_contents($cssFile);
        $cssToInlineStyles = new CssToInlineStyles();
        return $cssToInlineStyles->convert(
            $html,
            $css
        );
    }
}
