# WPMoo Code of Conduct

We want contributions to feel welcoming, collaborative, and consistent. Please follow these guidelines whenever you propose a change or open a pull request against the WPMoo framework.

## Community Expectations

- Be respectful, patient, and inclusive. Offer constructive feedback and seek clarification instead of assuming intent.
- Default to public discussion (issues or pull requests) so decisions remain transparent and future contributors can learn from the context.
- Credit the work of others and reference any external sources or inspirations you use.

## Coding Principles

- Use the dedicated Moo facade helpers (`Moo::page()`/`Moo::container()`, `Moo::section()`, `Moo::metabox()`, `Moo::panel()`) in new code. And follow this method.
- Attach sections to option pages with `->parent('page_id')` and to metaboxes with `->metabox($metaboxHandleOrId)` so every fluent definition reads the same regardless of context.
- Keep bootstrapping code lightweight—prefer small registrar classes and make reusable configuration explicit.
- Add concise comments only when they remove ambiguity; otherwise let the code express the intent.
- Follow the existing PHP coding standards (WordPress PHPCS ruleset) and run linters/tests before submitting patches.

## Communication

- Document behavioural changes, migrations, or breaking updates in the pull request description.
- Highlight any follow-up tasks or trade-offs so maintainers can plan subsequent work.
- Respond to review feedback promptly and clarify open questions.

## Enforcement

The maintainers may close, modify, or request changes to contributions that do not fit these expectations. Repeated violations or disrespectful behaviour can result in account restrictions or removal from the project.

Thank you for helping keep WPMoo a friendly and reliable framework.
