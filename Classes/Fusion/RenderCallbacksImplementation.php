<?php
namespace Neos\Form\FusionRenderer\Fusion;

use Neos\Form\Core\Model\Renderable\RootRenderableInterface;
use Neos\Form\Core\Runtime\FormRuntime;
use Neos\Fusion\FusionObjects\AbstractFusionObject;

/**
 * A Fusion implementation that invokes the Form "RenderCallbacks", can be used as processor like
 *
 * someElement.@process.renderCallbacks = Neos.Form.FusionRenderer:RenderCallbacks {
 *   formRuntime = ${formRuntime}
 *   formElement = ${containerElement}
 * }
 */
class RenderCallbacksImplementation extends AbstractFusionObject
{
    private function getValue(): string
    {
        return $this->fusionValue('value');
    }

    private function getFormRuntime(): FormRuntime
    {
        return $this->fusionValue('formRuntime');
    }

    /**
     * @return RootRenderableInterface|null
     */
    private function getFormElement()
    {
        return $this->fusionValue('formElement');
    }

    public function evaluate()
    {
        $formRuntime = $this->getFormRuntime();
        $formElement = $this->getFormElement();
        if ($formElement !== null) {
            $formElement->beforeRendering($formRuntime);
        }
        $renderedFormElement = $this->getValue();
        $renderedFormElement = $formRuntime->invokeRenderCallbacks($renderedFormElement, $formElement ?: $formRuntime);
        return $renderedFormElement;
    }
}