<?php
namespace Neos\Form\FusionRenderer\Fusion;

use Neos\Form\Core\Model\Renderable\RootRenderableInterface;
use Neos\Form\Core\Runtime\FormRuntime;
use Neos\Fusion\FusionObjects\AbstractFusionObject;

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
        $renderedFormElement = $this->getValue();
        $formRuntime = $this->getFormRuntime();
        $renderedFormElement = $formRuntime->invokeRenderCallbacks($renderedFormElement, $this->getFormElement() ?: $formRuntime);
        return $renderedFormElement;
    }
}