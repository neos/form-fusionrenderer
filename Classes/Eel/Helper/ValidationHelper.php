<?php
namespace Wwwision\Form\FusionPreset\Eel\Helper;

use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Error\Messages\Result;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Utility\ObjectAccess;

class ValidationHelper implements ProtectedContextAwareInterface
{

    public function value(ActionRequest $request, string $elementName): ?string
    {
        $submittedArguments = $request->getInternalArgument('__submittedArguments');
        if ($submittedArguments === null) {
            return null;
        }
        return ObjectAccess::getPropertyPath($submittedArguments, $elementName);
    }

    public function validationErrors(ActionRequest $request, string $elementName): array
    {
        /** @var Result $validationResults */
        $validationResults = $request->getInternalArgument('__submittedArgumentValidationResults');
        if ($validationResults === null) {
            return [];
        }
        return $validationResults->forProperty($elementName)->getErrors();
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