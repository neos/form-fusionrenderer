<?php
namespace Neos\Form\FusionRenderer;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Package\PackageManagerInterface;
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

    /**
     * @Flow\Inject
     * @var PackageManagerInterface
     */
    protected $packageManager;

    public function setControllerContext(ControllerContext $controllerContext)
    {
        $this->controllerContext = $controllerContext;
    }

    public function setFormRuntime(FormRuntime $formRuntime)
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
        $fusionView->setPackageKey('Neos.Form.FusionRenderer');
        $fusionView->setFusionPathPatterns([
            $this->packageManager->getPackage('Neos.Fusion')->getResourcesPath() . 'Private/Fusion',
            $this->packageManager->getPackage('Neos.Form.FusionRenderer')->getResourcesPath() . 'Private/Fusion',
        ]);
        $fusionView->setFusionPath('form');
        $fusionView->assign('formRuntime', $formRuntime);
        $output = $fusionView->render();
        #$output = $formRuntime->invokeRenderCallbacks($output, $formRuntime);
        return $output;
    }
}