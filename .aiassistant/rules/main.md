---
apply: always
---

# AI Assistant Guidelines

@version 1.8.6

## General Coding Standards for all contexts
- Use Yoda conditional style.
- Do not include inline comments.
- Use strict type checking `===`.
- Use single quotes for strings.

## PHP Coding Standards
- Always begin with "\" when calling native PHP functions to make them fully qualified.
- Always refer to user-defined and WordPress functions without a leading `\`, even if inside a namespace.
- Keep all conditions within an if statement on the same line.
- Prefer `use` imports over fully qualified classes.
- Do not use `use` imports for functions or classes in the global namespace.
- Prefer `isset` over `empty`.
- Use `[] ===` for checking if an array is empty.
- Use strict type checking `===` unless using PHP native functions which return boolean (e.g., `is_array`, `isset`, etc.)
- Do not use the `empty` function.
- Include array shapes in docblocks.
- Include return types in all functions.
- Use arrow functions when adding filters to ServiceProviderInterface.
- Use modern string functions like `str_contains`, `str_starts_with`, and `str_ends_with`.
- Do not use `static` before closure (anonymous) functions.
- Put closure (anonymous) functions on the same line as the function call.
- Prefer closure (anonymous) functions over arrow functions when calling array functions like `array_map`, `array_filter`, etc.
- Do not use short ternaries.
- Do not include inline comments.
- @phpstan-type should be written in CONSTANT_CASE.

## TypeScript Coding Standards
- New properties added to types should not be optional.
- Do not include inline comments.
- Use strict boolean expressions in conditions (e.g., `'' !== variable` or `null === variable` instead of `! variable` or `&& variable`).
- Do not use nested ternaries.
- Constants created within object destructuring should be in alphabetical order.

## PostCSS Coding Standards
- File names should be in kebab-case.
- Newly created .pcss files should contain a single .wrap{} class only.
- CSS or PostCSS class names should be in kebab-case even when in PostCSS modules.
- Any ":hover" statements must be wrapped in "@media (any-hover: hover) {}".
- Width-based media queries should use the available @custom-media variables. (e.g., `@media (--mobile) {}`).
- Media queries should be contained within the classes they are targeting. Not split out into a different section in the .pcss file.
- Focus styles should be contained within the classes they are targeting. (e.g., `&:focus {}`)

## Svelte Coding Standards
- Always use Svelte 5 syntax.
- Pass generic types to all rune calls. (e.g., type Props = {foo: string}; let {foo = $bindable<string>()} = $props<Props>()).
- Use an external <lowercase name of svelte file>.module.pcss file for styling.

## PHPUnit Coding Standards
- All test cases must extend `\WP_UnitTestCase` instead of `\PHPUnit\Framework\TestCase`.
- Use actual classes as integration tests instead of creating mocks.
- Test functions must go before private or public static functions.
- Test case function names must be snake case.
- Do not include inline comments
- Prefer `assertSame` over `assertEquals`.
- Data providers should use keyed arrays with keys in kebab-case.
- Data providers values should use keyed arrays with keys in kebab-case.
- Data provider function names MUST be camelCase.
- Data provider functions MUST be declared as `public static`.
- Test functions using a data provider MUST accept one parameter for each value provided, rather than a single array; parameter names MUST match the keys of the data provider.
- Test function parameter types MUST be strictly typed.
- Use PHP 8+ function attributes (`#[DataProvider(...)]`) to specify data providers for tests.
- Do not include inline comments.
- Use PHPUnit 11 for testing. Consider deprecated phpunit methods.

## Jest Coding Standards
- File names should be in kebab-case.
- File names should match the name of the file being tested with `.test` between the name and extension.
- Tests should live in the "js/jest/tests" directory using the same directory structure as the file being tested uses in "js/src".
- Do not include inline comments.
- Use 2 lines between test cases.
- Import real modules instead of mocking them.
- Use actual implementations for testing.
- Set up proper test data that works with the real code.
- Use `yarn` to run tests instead of `npm`
