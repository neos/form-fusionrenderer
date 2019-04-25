# Neos.Form.FusionRenderer

A [Flow Form Framework](https://github.com/neos/form) preset for [Fusion](https://neos.readthedocs.io/en/stable/CreatingASite/Fusion/index.html) based Form rendering

The Flow Form Framework comes with a preset that uses [Fluid](http://flowframework.readthedocs.io/en/stable/TheDefinitiveGuide/PartIII/Templating.html)
to render Form Elements by default.
This package allows to use `Fusion` instead to render Forms.

## Related Packages

Make sure to have a look at the other Flow Form Framework [Related Packages](https://github.com/neos/form/#related-packages)

## Usage

Install this package using [composer](https://getcomposer.org/):

```
composer require neos/form-fusionrenderer
```

> **Note:** This package requires the `neos/form` package in version 4.1 or higher

Afterwards a new Form preset `fusion` is available to be used/extended.
This preset extends the `default` preset of the Flow Form Framework and it
comes with a Fusion prototype for each of the `default` Form Element definitions.

When using it together with the `Neos.NodeTypes.Form` package the preset can
be changed like this:

```fusion
prototype(Neos.NodeTypes.Form:Form) {
  presetName = 'fusion'
}
```

When rendering a form from Fluid, the preset can be set in the corresponding
ViewHelper:

```html
{namespace form=Neos\Form\ViewHelpers}
 <form:render factoryClass="NameOfYourCustomFactoryClass" presetName="fusion" />
```

> **Note:** It's recommended to extend/create your own preset to adjust it to your needs

See [Form Framework documentation](https://flow-form-framework.readthedocs.io/en/stable/adjusting-form-output.html#presets-explained)
for more details about presets.

### Adjust the rendering

By default the `fusion` preset renders a form similar to what the `default`
preset renders (if the Fluid Templates haven't been modified) with the following
exceptions:

* The `ImageUpload` & `FileUpload` fields won't render any inline JavaScript
* The `DatePicker` field won't render a JS based date picker but use the HTML5 type attribute instead

The Fusion prototypes for rendering Form Elements are expected to have the
same name as the corresponding Form Element definition, including namespace.
So for the `Neos.Form:SingleLineText` there is a corresponding Fusion prototype
with the same name for example.

Any valid Fusion object can be used and they will have access to the following
context variables:

* `formRuntime` The current `Neos\Form\Core\Runtime\FormRuntime` instance
* `element` The actual `Neos\Form\Core\Model\FormElementInterface` instance representing the current Form Element
* `containerElement` The parent `Neos\Form\Cor\Model\AbstractSection` (e.g. Section or Page) of the current Form Element

All provided Form Element prototypes extend the [Neos.Form.FusionRenderer:FormElement](Resources/Private/Fusion/Core/FormElement.fusion)
to make it easier to adjust the rendering for all elements.

> **Tip:** Have a look at the existing Form Element Fusion Prototypes to see how simple they are

**Important:** When overriding Fusion prototypes make sure that the [Package loading order](http://flowframework.readthedocs.io/en/stable/TheDefinitiveGuide/PartIII/PackageManagement.html#loading-order) is set correctly (i.e. that the package with the customizations has a dependency to the `neos/form-fusionrenderer` package) or else they might not have any effect.

#### Example: Render Form Element label as placeholder

To render Form Element labels as placeholders of the corresponding input field,
the folling Fusion snippet works:

```fusion
prototype(Neos.Form.FusionRenderer:FormElement) {
    # remove the whole label rendering
    label >

    # set the fields "placeholder" attribute to the Form Element Label
    fieldContainer.field.attributes.placeholder = ${element.label}
}
```

### Adding custom Form Element Types

This package comes with Fusion Prototypes for all Form Element definitions
of the `default` preset.
The real strength of the Form Framework comes with custom presets and Form Elements.

#### Example: Custom Email address Form Element

Rather than using the `SingleLineText` Element with the `EmailAddressValidator`
it's a good idea to create a custom field for email addresses.
This makes it easier to adjust the looks and behavior of the field and makes it
much easier to use.

First, the new Form Element definition is needed in the respective Form preset
(We assume the `fusion` preset here):

*Settings.yaml*:

```yaml
Neos:
  Form:
    presets:
      'fusion':
        formElementTypes:
          'Your.Package:EmailAddress':
            superTypes:
              'Neos.Form:FormElement': true
              'Neos.Form:TextMixin': true
            validators:
              -
                identifier: 'Neos.Flow:EmailAddress'
```

> **Note:** In this and the following examples, feel free to replace "Your.Package" with your own package key

Now that `Your.Package:EmailAddress` Form Element can be used in any Form Definition
that is rendered via the `fusion` preset.

Trying to render it, however, will lead to an exception:

```markdown
The Fusion object `Your.Package:EmailAddress` is not completely defined (missing property `@class`). Most likely you didn't inherit from a basic object.
```

Let's change this by defining the Fusion object:

*EmailAddressFormElement.fusion*:

```fusion
prototype(Your.Package:EmailAddress) < prototype(Neos.Form.FusionRenderer:FormElement) {
    fieldContainer {
        field {
            tagName = 'input'
            attributes {
                type = 'email'
                name = ${elementName}
                value = ${elementValue}
            }
        }
    }
}
```

That's all. Most of the rendering logic is defined in the `Neos.Form.FusionRenderer:FormElement` object,
only the tag name and some attributes have to be specified.

> **Note:** In the Form Element definition we attached the `EmailAddressValidator`, so this doesn't have
  to be added manually. In addition we set the input type attribute to `email` which adds browser validation, too

#### Example: Custom Composite Form Element

Another very good use for custom Form Elements are composite elements. Those
are elements that render more than one input. The `Neos.Form:PasswordWithConfirmation`
Form Element is one example of the `default` preset.

Let's add a custom Field that renders honorific title, given name and family
name fields at once.

First we add the Form Element definition

*Settings.yaml*:

```yaml
Neos:
  Form:
    presets:
      'fusion':
        formElementTypes:
          'Your.Package:NameAndTitle':
            superTypes:
              'Neos.Form:FormElement': true
            properties:
              # options for the honorific title
              options:
                'mr': 'Mr.'
                'mrs': 'Mrs.'
                'dr': 'Dr.'
```

And the corresponding Fusion object to render the Form Element:

*NameAndTitleFormElement.fusion*:

```fusion
prototype(Your.Package:NameAndTitle) < prototype(Neos.Form.FusionRenderer:FormElement) {
    fieldContainer {
        field = Neos.Fusion:Array {
            title = Neos.Form.FusionRenderer:FormElementField {
                tagName = 'select'
                attributes {
                    name = ${elementName + '[title]'}
                }
                content = Neos.Form.FusionRenderer:SelectOptions {
                    itemRenderer = Neos.Fusion:Tag {
                        tagName = 'option'
                        attributes.value = ${optionValue}
                        attributes.selected = ${optionSelected}
                        content = ${optionLabel}
                    }
                }
            }
            givenName = Neos.Form.FusionRenderer:FormElementField {
                tagName = 'input'
                attributes {
                    type = 'text'
                    name = ${elementName + '[givenName]'}
                    value = ${elementValue.givenName}
                }
            }
            familyName = Neos.Form.FusionRenderer:FormElementField {
                tagName = 'input'
                attributes {
                    type = 'text'
                    name = ${elementName + '[familyName]'}
                    value = ${elementValue.familyName}
                }
            }
        }
    }
}
```

In this case we replace the `field` to be an `Neos.Fusion:Array`.

> **Note:** The element type of the composite element will be `array`, you can refer to the
  individual values (e.g. in the ConfirmationFinisher message) via dot-syntax (for example `theElement.givenName`)
