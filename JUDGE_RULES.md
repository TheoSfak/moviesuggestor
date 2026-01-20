# Judge Rules

The Judge evaluates the movie suggester application based on the following criteria:

## Pass Criteria

### Phase 1: Foundation (Current)
- [ ] PHPUnit tests must run successfully (`vendor/bin/phpunit`)
- [ ] All tests must pass (exit code 0)
- [ ] Database schema must exist (schema.sql)
- [ ] At least one test file exists in tests/
- [ ] composer.json exists with PHPUnit configured
- [ ] No PHP syntax errors

### Phase 2: Core Features (After Phase 1 passes)
- [ ] User can select a movie category from dropdown
- [ ] User can select minimum score
- [ ] App returns matching movies
- [ ] Empty results handled gracefully (no crashes)
- [ ] Each movie has a trailer link

### Phase 3: Robustness (After Phase 2 passes)
- [ ] Edge cases tested (empty DB, invalid input)
- [ ] SQL injection protection
- [ ] XSS protection for output
- [ ] All features have test coverage

## Failure Conditions
- Any test failure
- PHP syntax errors
- Missing required files for current phase
- Crashes or uncaught exceptions
