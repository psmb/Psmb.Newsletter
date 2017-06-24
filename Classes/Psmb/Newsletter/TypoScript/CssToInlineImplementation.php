<?php
namespace Psmb\Newsletter\TypoScript;

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\FusionObjects\AbstractFusionObject;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

/**
 * A TypoScript Object that inlines styles
 */
class CssToInlineImplementation extends AbstractFusionObject {
    /**
     * `value` should contain html to be processed
     * `cssPath` should contain a path to CSS file, e.g. `cssPath = 'resource://Psmb.Newsletter/Public/styles.css'`
     * @return string
     */
    public function evaluate() {
        $html = $this->tsValue('value');
        $cssFile = $this->tsValue('cssPath');
        $css = file_get_contents($cssFile);
        $cssToInlineStyles = new CssToInlineStyles();
        return $cssToInlineStyles->convert(
            $html,
            $css
        );
    }
}
