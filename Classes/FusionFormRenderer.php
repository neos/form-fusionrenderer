<?php
namespace Wwwision\Form\FusionPreset;

use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Form\Core\Model\Renderable\RootRenderableInterface;
use Neos\Form\Core\Renderer\RendererInterface;
use Neos\Form\Core\Runtime\FormRuntime;
use Neos\Fusion\View\FusionView;

class FusionFormRenderer implements RendererInterface
{
    /**
     * @var ControllerContext
     */
    private $controllerContext;

    /**
     * @var FormRuntime
     */
    private $formRuntime;

    public function setControllerContext(ControllerContext $controllerContext): void
    {
        $this->controllerContext = $controllerContext;
    }

    public function setFormRuntime(FormRuntime $formRuntime): void
    {
        $this->formRuntime = $formRuntime;
    }

    public function getFormRuntime(): FormRuntime
    {
        return $this->formRuntime;
    }

    public function renderRenderable(RootRenderableInterface $formRuntime): string
    {
        $formRuntime->beforeRendering($this->formRuntime);

        if (!$formRuntime instanceof FormRuntime) {
            // TODO exception
            return '';
        }

        $fusionView = new FusionView();
        $fusionView->setControllerContext($this->controllerContext);
        $fusionView->disableFallbackView();
        $fusionView->setPackageKey('Wwwision.Form.FusionPreset');
        $fusionView->setFusionPathPatterns([
            FLOW_PATH_PACKAGES . 'Neos/Neos.Fusion/Resources/Private/Fusion',
            FLOW_PATH_PACKAGES . 'Application/Wwwision.Form.FusionPreset/Resources/Private/Fusion',
        ]);
        $fusionView->setFusionPath('form');
        $fusionView->assign('formRuntime', $formRuntime);
        $output = $fusionView->render();
        #$output = $formRuntime->invokeRenderCallbacks($output, $formRuntime);
        return $output;
    }
}