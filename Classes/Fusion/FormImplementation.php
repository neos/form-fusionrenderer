<?php
namespace Wwwision\Form\FusionPreset\Fusion;

use Neos\Form\Core\Model\Page;
use Neos\Form\Core\Model\Renderable\RenderableInterface;
use Neos\Form\Core\Runtime\FormRuntime;
use Neos\Fusion\FusionObjects\AbstractFusionObject;
use Neos\Fusion\FusionObjects\TagImplementation;

class FormImplementation extends AbstractFusionObject
{

    public function isEditMode(): bool
    {
        return $this->fusionValue('editMode') === true;
    }

    public function evaluate()
    {
        $context = $this->runtime->getCurrentContext();
        /** @var FormRuntime $formRuntime */
        $formRuntime = $context['formRuntime'];
        $output = '';
        if ($this->isEditMode()) {
            /** @var Page $formPage */
            foreach ($formRuntime->getPages() as $formPage) {
                $output .= $this->renderFormPage($formPage, $context);
            }
        } else {
            $output .= $this->renderFormPage($formRuntime->getCurrentPage(), $context);
        }

        $output .= $this->runtime->render($this->path . '/navigationRenderer');

        return $output;

    }

    protected function renderFormPage(Page $page, array &$context): string
    {
        $output = '';
        /** @var RenderableInterface $formElement */
        foreach ($page->getRenderablesRecursively() as $formElement) {
            $context['element'] = $formElement;
            $this->runtime->pushContextArray($context);
            $output .= $this->runtime->render($this->path . '/elementRenderer');
            $this->runtime->popContext();
        }

        /** @var FormRuntime $formRuntime */
        $formRuntime = $context['formRuntime'];
        $output = $formRuntime->invokeRenderCallbacks($output, $formRuntime->getCurrentPage());
        return $output;
    }
}