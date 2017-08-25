<?php
namespace Neos\Form\FusionRenderer\Eel\Helper;

use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Error\Messages\Result;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\I18n\Translator;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\ResourceManagement\Exception as ResourceException;
use Neos\Form\Core\Model\FormElementInterface;
use Neos\Form\Core\Model\Renderable\AbstractRenderable;
use Neos\Form\Core\Model\Renderable\RootRenderableInterface;
use Neos\Form\Core\Runtime\FormRuntime;
use Neos\Utility\ObjectAccess;

class FormHelper implements ProtectedContextAwareInterface
{

    /**
     * @Flow\Inject
     * @var Translator
     */
    protected $translator;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    public function elementValue(FormRuntime $formRuntime, RootRenderableInterface $element)
    {
        $request = $formRuntime->getRequest();
        /** @var Result $validationResults */
        $validationResults = $formRuntime->getRequest()->getInternalArgument('__submittedArgumentValidationResults');
        if ($validationResults !== null && $validationResults->forProperty($element->getIdentifier())->hasErrors()) {
            return $this->getLastSubmittedFormData($request, $element->getIdentifier());
        }
        return ObjectAccess::getPropertyPath($formRuntime, $element->getIdentifier());
    }

    private function getLastSubmittedFormData(ActionRequest $request, string $propertyPath)
    {
        $submittedArguments = $request->getInternalArgument('__submittedArguments');
        if ($submittedArguments === null) {
            return null;
        }
        return ObjectAccess::getPropertyPath($submittedArguments, $propertyPath);
    }

    public function identifier($object): string
    {
        if (is_array($object) && isset($object['__identity'])) {
            return $object['__identity'];
        }
        if (is_string($object)) {
            return $object;
        }
        if (!is_object($object)) {
            return '';
        }
        return (string)$this->persistenceManager->getIdentifierByObject($object);
    }

    public function hasValidationErrors(FormRuntime $formRuntime, RootRenderableInterface $element): bool
    {
        return $this->getValidationResult($formRuntime, $element)->hasErrors();
    }

    public function validationErrors(FormRuntime $formRuntime, RootRenderableInterface $element): array
    {
        return $this->getValidationResult($formRuntime, $element)->getErrors();
    }

    private function getValidationResult(FormRuntime $formRuntime, RootRenderableInterface $element): Result
    {
        /** @var Result $validationResults */
        $validationResults = $formRuntime->getRequest()->getInternalArgument('__submittedArgumentValidationResults');
        if ($validationResults === null) {
            return new Result();
        }
        return $validationResults->forProperty($element->getIdentifier());
    }

    public function translateAndEscapeProperty(AbstractRenderable $element, string $property): string
    {
        return $this->escape($this->translateProperty($element, $property));
    }

    public function translateProperty(AbstractRenderable $element, string $property): string
    {
        if ($property === 'label') {
            $defaultValue = $element->getLabel();
            if ($defaultValue === null) {
                $defaultValue = '';
            }
        } elseif ($element instanceof FormElementInterface) {
            $defaultValue = isset($element->getProperties()[$property]) ? (string)$element->getProperties()[$property] : '';
        } else {
            $defaultValue = '';
        }
        $translationId = sprintf('forms.elements.%s.%s', $element->getIdentifier(), $property);
        return $this->translate($element, $translationId, $defaultValue);
    }

    public function translate(RootRenderableInterface $element, string $translationId, string $defaultValue): string
    {
        $renderingOptions = $element->getRenderingOptions();
        if (!isset($renderingOptions['translationPackage'])) {
            return $defaultValue;
        }
        try {
            $translation = $this->translator->translateById($translationId, [], null, null, 'Main', $renderingOptions['translationPackage']);
        } catch (ResourceException $exception) {
            return $defaultValue;
        }
        return $translation ?? $defaultValue;
    }

    public function escape(string $string): string
    {
        return htmlspecialchars($string, ENT_QUOTES);
    }

    /**
     * @param string $methodName
     * @return boolean
     */
    public function allowsCallOfMethod($methodName): bool
    {
        return true;
    }
}