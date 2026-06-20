Before implementing any feature or bugfix, load the `test-driven-development` skill and follow its instructions.

Always run `composer test` (PHPUnit) after any code changes and before committing. All 28+ tests must pass.

When adding new features (e.g. quick replies, new commands, conversations) OR modifying existing handlers (e.g. changing `hears()` patterns, response text), update or add tests in `tests/Feature/BotResponseTest.php` to cover the new behavior.

When modifying `hears()` patterns or conversation stop/skip conditions, add test cases that send the new input values (e.g. Thai text) and verify the handler responds correctly. Do not rely solely on old input values for coverage.