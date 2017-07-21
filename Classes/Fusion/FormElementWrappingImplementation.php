<?php
namespace Wwwision\Form\FusionPreset\Fusion;

use Neos\Form\Core\Model\Renderable\RenderableInterface;
use Neos\Form\Core\Model\Renderable\RootRenderableInterface;
use Neos\Form\Core\Runtime\FormRuntime;
use Neos\Fusion\FusionObjects\AbstractFusionObject;
use Neos\Fusion\FusionObjects\ArrayImplementation;
use org\bovigo\vfs\vfsStream;

class FormElementWrappingImplementation extends AbstractFusionObject
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