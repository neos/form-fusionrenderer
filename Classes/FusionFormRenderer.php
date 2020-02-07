<?php
namespace Neos\Form\FusionRenderer;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Package\PackageManager;
use Neos\Form\Core\Model\Renderable\RootRenderableInterface;
use Neos\Form\Core\Renderer\RendererInterface;
use Neos\Form\Core\Runtime\FormRuntime;
use Neos\Fusion\Core\Runtime;
use Neos\Fusion\Exception as FusionException;
use Neos\Fusion\View\FusionView;

/**
 * The main class of this package implementing the custom Form Renderer
 *
 * Usage:
 * This class is usually not instantiated manually but created via FormRuntime::render()
 * if the used Form Preset define this as rendererClassName
 *
 * If the root element of the corresponding FormDefinition has a "_fusionRuntime" rendering option
 * that FusionRuntime will be used to render the Fusion prototypes.
 * Otherwise a FusionView is used to render the "neos_form" Fusion path with the given FormRuntime
 */
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
     * @var PackageManager
     */
    protected $packageManager;

    /**
     * @Flow\InjectConfiguration(path="fusionAutoInclude")
     * @var array
     */
    protected $packagesForFusionAutoInclude;

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

    /**
     * Renders the given $formRuntime using Fusion
     *
     * If the $formRuntime specifies a "_fusionRuntime" rendering option that FusionRuntime
     * will be used, otherwise a new FusionView is instantiated.
     *
     * @param RootRenderableInterface $formRuntime
     * @return string
     * @throws FusionException
     * @throws \Neos\Flow\Mvc\Exception
     * @throws \Neos\Flow\Package\Exception\UnknownPackageException
     * @throws \Neos\Flow\Security\Exception
     */
    public function renderRenderable(RootRenderableInterface $formRuntime): string
    {
        if (!$formRuntime instanceof FormRuntime) {
            throw new FusionException(sprintf('Expected instance of FormRuntime, got %s', is_object($formRuntime) ? get_class($formRuntime) : gettype($formRuntime)), 1503932881);
        }
        $formRuntime->beforeRendering($this->formRuntime);
        $renderingOptions = $this->formRuntime->getRenderingOptions();
        if (isset($renderingOptions['_fusionRuntime'])) {
            $fusionRuntime = $renderingOptions['_fusionRuntime'];
            if (!$fusionRuntime instanceof Runtime) {
                throw new FusionException(sprintf('Expected instance of FusionRuntime, got %s', is_object($fusionRuntime) ? get_class($fusionRuntime) : gettype($fusionRuntime)), 1503932883);
            }
            $fusionRuntime->pushContext('formRuntime', $this->formRuntime);
            $output = $fusionRuntime->render('neos_form');
            $fusionRuntime->popContext();
            return $output;
        }

        $fusionView = new FusionView();
        $fusionView->setControllerContext($this->controllerContext);
        $fusionView->setPackageKey('Neos.Form.FusionRenderer');

        $fusionView->setFusionPathPatterns(array_map(function (string $value) {
            return $this->packageManager->getPackage($value)->getResourcesPath() . 'Private/Fusion';
        }, array_keys(array_filter($this->packagesForFusionAutoInclude))));

        $fusionView->setFusionPath('neos_form');
        $fusionView->assign('formRuntime', $formRuntime);
        return $fusionView->render();
    }
}
