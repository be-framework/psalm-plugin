# be-framework/psalm-plugin

[![Test](https://github.com/be-framework/psalm-plugin/actions/workflows/test.yml/badge.svg)](https://github.com/be-framework/psalm-plugin/actions/workflows/test.yml)

Psalm plugin that detects [Be Framework](https://github.com/be-framework/Be.Framework) runtime errors at static-analysis time.
It also teaches Psalm taint analysis that constructor parameters annotated with
`Ray\InputQuery\Attribute\Input` are user-controlled input on configured root input classes.

## What it detects

### `MissingBeingParameterAttribute`

A Being class constructor parameter that lacks both `#[Input]` (`Ray\InputQuery\Attribute\Input`) and `#[Inject]` (`Ray\Di\Di\Inject`). At runtime this raises `Be\Framework\Exception\MissingParameterAttribute` from `BecomingArguments`.

```php
final readonly class WeightLogged
{
    public function __construct(
        #[Input] public string $recordedAt,
        public StorageInterface $storage, // ← missing attribute, reported
    ) {}
}
```

A class is treated as a Being when its constructor declares at least one `#[Input]` parameter.

### `ConflictingBeingParameterAttribute`

A Being constructor parameter that carries **both** `#[Input]` and `#[Inject]`. At runtime this raises a conflict error from `BecomingArguments`.

```php
public function __construct(
    #[Input, Inject] public StorageInterface $storage, // ← reported
) {}
```

### `InvalidValidateException`

A `#[Validate]` method that throws an exception not extending `\DomainException`. `Be\Framework\SemanticVariable\SemanticValidator` only catches `DomainException`, so other types silently bypass validation.

```php
#[Validate]
public function validate(string $value): void
{
    if ($value === '') {
        throw new \RuntimeException('empty'); // ← reported
    }
}
```

Variable throws and ternary/match unions are resolved via Psalm's `NodeTypeProvider`. When the type cannot be resolved, the plugin stays silent (false-positive avoidance).

### `#[Input]` taint sources

When Psalm is run with `--taint-analysis`, only constructor parameters annotated with
`Ray\InputQuery\Attribute\Input` on configured root input classes are treated as
user-controlled input. `#[Inject]` parameters and unrelated variables are not
tainted. Downstream input classes are not re-tainted after sanitization unless
they are explicitly configured as sources.

Configure the first input classes in `psalm.xml`:

```xml
<plugins>
    <pluginClass class="Be\PsalmPlugin\Plugin">
        <inputTaintSources>
            <class name="App\Input\ProfileInput" />
        </inputTaintSources>
    </pluginClass>
</plugins>
```

```php
final readonly class ProfileInput
{
    public function __construct(
        #[Input] public string $name,
    ) {}

    public function render(): void
    {
        echo $this->name; // reported by Psalm as TaintedHtml
    }
}
```

Taint analysis is enabled separately from normal Psalm analysis:

```bash
vendor/bin/psalm --taint-analysis
```

There is a runnable demo in [`demo/`](demo/):

```bash
vendor/bin/psalm --config=demo/psalm.xml --taint-analysis --no-cache --no-progress
```

## Installation

```bash
composer require --dev be-framework/psalm-plugin
```

Register the plugin in your `psalm.xml`:

```xml
<plugins>
    <pluginClass class="Be\PsalmPlugin\Plugin" />
</plugins>
```

Or use the Psalm plugin installer:

```bash
vendor/bin/psalm-plugin enable be-framework/psalm-plugin
```

## Requirements

- PHP 8.3+
- Psalm 6.x

## Suppressing issues

Standard Psalm suppression works at the class or method level:

```php
/** @psalm-suppress MissingBeingParameterAttribute */
final readonly class IntentionallyLoose
{
    // ...
}
```

## License

MIT
