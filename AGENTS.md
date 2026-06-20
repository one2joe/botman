Always run `composer test` (PHPUnit) after any code changes and before committing. All 28+ tests must pass.

When adding new features (e.g. quick replies, new commands, conversations), update or add tests in `tests/Feature/BotResponseTest.php` to cover the new behavior.