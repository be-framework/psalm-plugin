# `#[Input]` Taint Demo

This demo shows that the plugin treats only configured root input classes as
Psalm taint sources. Downstream input classes are not re-tainted after
sanitization.

Run it from the repository root:

```bash
vendor/bin/psalm --config=demo/psalm.xml --taint-analysis --no-cache --no-progress
```

Expected result:

- Psalm exits non-zero because the unsafe output is intentional.
- `GreetingInput::unsafeOutput()` reports `TaintedHtml` and `TaintedTextWithQuotes` for `echo $this->name`.
- `GreetingInput::sanitizedInput()` returns a downstream input object with an escaped value.
- `SanitizedGreetingInput::output()` is accepted because the downstream input class is not configured as a source.
- `#[Inject]` values are not tainted.
