<?php
namespace Neos\Form\FusionRenderer;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Package\PackageManagerInterface;
use Neos\Form\Core\Model\Renderable\RootRenderableInterface;
use Neos\Form\Core\Renderer\RendererInterface;
use Neos\Form\Core\Runtime\FormRuntime;
use Neos\Fusion\Core\Runtime;
use Neos\Fusion\Exception as FusionException;
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
        $renderingOptions = $this->formRuntime->getRenderingOptions();
        if (isset($renderingOptions['_fusionRuntime'])) {
            $fusionRuntime = $renderingOptions['_fusionRuntime'];
            if (!$fusionRuntime instanceof Runtime) {
                // TODO
                throw new FusionException();
            }
            $fusionRuntime->pushContext('formRuntime', $this->formRuntime);
            $output = $fusionRuntime->render('form');
            $fusionRuntime->popContext();
            return $output;
        }

        $fusionView = new FusionView();
        $fusionView->setControllerContext($this->controllerContext);
        $fusionView->disableFallbackView();
        $fusionView->setPackageKey('Neos.Form.FusionRenderer');
        $fusionView->setFusionPathPatterns([
            $this->packageManager->getPackage('Neos.Fusion')->getResourcesPath() . 'Private/Fusion',
            $this->packageManager->getPackage('Neos.Form.FusionRenderer')->getResourcesPath() . 'Private/Fusion/Core',
            $this->packageManager->getPackage('Neos.Form.FusionRenderer')->getResourcesPath() . 'Private/Fusion/ContainerElements',
            $this->packageManager->getPackage('Neos.Form.FusionRenderer')->getResourcesPath() . 'Private/Fusion/Elements',
        ]);
        $fusionView->setFusionPath('form');
        $fusionView->assign('formRuntime', $formRuntime);
        $output = $fusionView->render();
        #$output = $formRuntime->invokeRenderCallbacks($output, $formRuntime);
        return $output;
    }
}