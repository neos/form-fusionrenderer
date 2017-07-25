<?php
namespace Wwwision\Form\FusionPreset\Eel\Helper;

use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Error\Messages\Result;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Form\Core\Model\FormElementInterface;
use Neos\Form\Core\Runtime\FormRuntime;
use Neos\Utility\ObjectAccess;

class FormHelper implements ProtectedContextAwareInterface
{

    public function elementValue(FormRuntime $formRuntime, FormElementInterface $element)
    {
        $request = $formRuntime->getRequest();
        /** @var Result $validationResults */
        $validationResults = $formRuntime->getRequest()->getInternalArgument('__submittedArgumentValidationResults');
        if ($validationResults !== null && $validationResults->hasErrors()) {
            return $this->getLastSubmittedFormData($request, $element);
        }
        return ObjectAccess::getPropertyPath($formRuntime, $element->getIdentifier());
    }

    private function getLastSubmittedFormData(ActionRequest $request, FormElementInterface $element)
    {
        $submittedArguments = $request->getInternalArgument('__submittedArguments');
        if ($submittedArguments === null) {
            return null;
        }
        return ObjectAccess::getPropertyPath($submittedArguments, $element->getIdentifier());
    }

    public function hasValidationErrors(FormRuntime $formRuntime, FormElementInterface $element): bool
    {
        return $this->getValidationResult($formRuntime, $element)->hasErrors();
    }

    public function validationErrors(FormRuntime $formRuntime, FormElementInterface $element): array
    {
        return $this->getValidationResult($formRuntime, $element)->getErrors();
    }

    private function getValidationResult(FormRuntime $formRuntime, FormElementInterface $element): Result
    {
        /** @var Result $validationResults */
        $validationResults = $formRuntime->getRequest()->getInternalArgument('__submittedArgumentValidationResults');
        if ($validationResults === null) {
            return new Result();
        }
        return $validationResults->forProperty($element->getIdentifier());
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