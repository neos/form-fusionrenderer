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

/**
 * Eel Helper with some convenience methods for Fusion based Form rendering
 */
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

	/**
	 * @var ?array
	 * @Flow\InjectConfiguration(path="FusionRenderer.formHelper.mimeTypes", package="Neos.Form")
	 */
	protected ?array $mimeTypes;

    /**
     * Returns the value of a given Form Element.
     * If there are validation errors for the element, the previously submitted value will be returned.
     *
     * @param FormRuntime $formRuntime The current FormRuntime instance
     * @param RootRenderableInterface $element The element to fetch the value for
     * @return mixed|null
     */
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

    /**
     * Return the submitted data for a given $propertyPath
     * @see elementValue()
     *
     * @param ActionRequest $request
     * @param string $propertyPath
     * @return mixed|null
     */
    private function getLastSubmittedFormData(ActionRequest $request, string $propertyPath)
    {
        $submittedArguments = $request->getInternalArgument('__submittedArguments');
        if ($submittedArguments === null) {
            return null;
        }
        return ObjectAccess::getPropertyPath($submittedArguments, $propertyPath);
    }

    /**
     * Whether the given Form Element has validation errors
     *
     * @param FormRuntime $formRuntime
     * @param RootRenderableInterface $element
     * @return bool
     */
    public function hasValidationErrors(FormRuntime $formRuntime, RootRenderableInterface $element): bool
    {
        return $this->getValidationResult($formRuntime, $element)->hasErrors();
    }

    /**
     * Returns all validation errors for a given Form Element
     *
     * @param FormRuntime $formRuntime
     * @param RootRenderableInterface $element
     * @return array
     */
    public function validationErrors(FormRuntime $formRuntime, RootRenderableInterface $element): array
    {
        return $this->getValidationResult($formRuntime, $element)->getErrors();
    }

    /**
     * Retrieves the validation result object for a given Form Element
     * @see hasValidationErrors()
     * @see validationErrors()
     *
     * @param FormRuntime $formRuntime
     * @param RootRenderableInterface $element
     * @return Result
     */
    private function getValidationResult(FormRuntime $formRuntime, RootRenderableInterface $element): Result
    {
        /** @var Result $validationResults */
        $validationResults = $formRuntime->getRequest()->getInternalArgument('__submittedArgumentValidationResults');
        if ($validationResults === null) {
            return new Result();
        }
        return $validationResults->forProperty($element->getIdentifier());
    }

    /**
     * Returns the persistence identifier for a given object (Or an empty string if the given $object is no entity)
     *
     * @param mixed $object
     * @return string
     */
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

    /**
     * Translates the property of a given Form Element and htmlspecialchar's the result
     *
     * @param AbstractRenderable $element
     * @param string $property
     * @return string
     */
    public function translateAndEscapeProperty(AbstractRenderable $element, string $property): string
    {
        return $this->escape($this->translateProperty($element, $property));
    }

    /**
     * Translates the property of a given Form Element
     *
     * @param AbstractRenderable $element
     * @param string $property
     * @return string
     */
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

    /**
     * Translates arbitrary $tanslationIds using the package configured in the Form Element's renderingOptions
     *
     * @param RootRenderableInterface $element
     * @param string $translationId
     * @param string $defaultValue
     * @return string
     */
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

    /**
     * Htmlspecialchar's a given $string
     *
     * @param string $string
     * @return string
     */
    public function escape(string $string): string
    {
        return htmlspecialchars($string, ENT_QUOTES);
    }

	/**
	 * Get accept string for input attribute accept based on the allowed extensions array
	 *
	 * @param array $allowedExtensions
	 * @param bool $asFileExtension
	 * @param bool $asMimeType
	 * @return string
	 */
	public function getAcceptFromAllowedExtensions(array $allowedExtensions, bool $asFileExtension = true, bool $asMimeType = true): string {
		$accept = [];

		foreach ($allowedExtensions as $key => $extension) {
			if ($asFileExtension) {
				$accept[] = '.' . $extension;
			}

			if ($asMimeType) {
				$accept[] = $this->mimeTypes && array_key_exists($extension, $this->mimeTypes) ? $this->mimeTypes[$extension] : null;
			}
		}

		return implode(', ', $accept);
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