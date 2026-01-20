# Phase 2 Activation Checklist

**Status**: READY - Awaiting Judge GREEN approval  
**Created**: January 20, 2026

## Pre-Activation Requirements

- [ ] Judge CI/CD shows **GREEN** status for Phase 1
- [ ] All Phase 1 tests passing
- [ ] Phase 1 code reviewed and approved
- [ ] main branch is stable and deployable

## Activation Steps (Execute in Order)

### 1. Branch Setup
```bash
# Ensure on latest main
git checkout main
git pull origin main

# Create Phase 2 development branch
git checkout -b phase2-development

# Verify branch
git status
```

### 2. Copy Template Files
```bash
# Copy repository templates
cp phase2-prep/templates/FavoritesRepository.php.template src/FavoritesRepository.php
cp phase2-prep/templates/WatchLaterRepository.php.template src/WatchLaterRepository.php
cp phase2-prep/templates/RatingRepository.php.template src/RatingRepository.php
cp phase2-prep/templates/FilterBuilder.php.template src/FilterBuilder.php

# Copy migration files
mkdir -p migrations
cp phase2-prep/migrations/*.sql.template migrations/
cp phase2-prep/migrations/run-migrations.php.template migrations/run-migrations.php

# Copy test stubs
cp phase2-prep/tests/*.stub tests/
```

### 3. Activate Template Files
```bash
# Remove .template extensions from migrations
cd migrations
for f in *.template; do mv "$f" "${f%.template}"; done
cd ..

# Rename test stubs to actual test files
cd tests
for f in *.stub; do mv "$f" "${f%.stub}"; done
cd ..
```

### 4. Uncomment Code in Templates

Manually edit each file:
- [ ] src/FavoritesRepository.php - Uncomment all code
- [ ] src/WatchLaterRepository.php - Uncomment all code
- [ ] src/RatingRepository.php - Uncomment all code
- [ ] src/FilterBuilder.php - Uncomment all code
- [ ] migrations/*.sql - Uncomment SQL statements
- [ ] migrations/run-migrations.php - Uncomment PHP code
- [ ] tests/*Test.php - Uncomment test methods

### 5. Run Database Migrations
```bash
# Test migrations in dry-run mode first (if available)
php migrations/run-migrations.php --dry-run

# Run actual migrations
php migrations/run-migrations.php

# Verify schema changes
php validate-db.php
```

### 6. Update Dependencies (if needed)
```bash
# Install any new Composer dependencies
composer update

# Install PHPUnit if not present
composer require --dev phpunit/phpunit
```

### 7. Run Tests
```bash
# Run all tests including new Phase 2 tests
./vendor/bin/phpunit

# Verify all tests pass
# Expected: X tests, X assertions, 0 failures, 0 errors
```

### 8. Update .gitignore
```bash
# Backup current .gitignore
cp .gitignore .gitignore.phase1.backup

# Copy Phase 2 enhanced .gitignore
cp phase2-prep/.gitignore .gitignore
```

### 9. Initial Commit
```bash
# Stage all changes
git add .

# Commit with descriptive message
git commit -m "Phase 2: Initialize scaffolding

- Add FavoritesRepository, WatchLaterRepository, RatingRepository
- Add FilterBuilder for advanced queries
- Run database migrations (favorites, watch_later, ratings tables)
- Add Phase 2 test scaffolding
- Update .gitignore for Phase 2

All templates activated and ready for development."

# Push to remote
git push -u origin phase2-development
```

### 10. Verify Activation
- [ ] All files copied and activated
- [ ] Database migrations completed successfully
- [ ] All tests passing (including incomplete stubs)
- [ ] No syntax errors in PHP files
- [ ] Git history clean and descriptive
- [ ] Branch pushed to remote

## Post-Activation Tasks

### Documentation Updates
- [ ] Update README.md with Phase 2 features
- [ ] Update API documentation
- [ ] Create Phase 2 user guide

### Development Planning
- [ ] Create GitHub issues for Phase 2 features
- [ ] Set up project board for Phase 2
- [ ] Assign tasks to developers (if team)
- [ ] Schedule code reviews

### Testing Strategy
- [ ] Implement all test stubs
- [ ] Add integration tests
- [ ] Add E2E tests for new features
- [ ] Set up continuous testing

## Rollback Plan (If Needed)

If activation fails or issues arise:

```bash
# Discard Phase 2 changes
git checkout main
git branch -D phase2-development

# Database rollback
mysql -u root -p moviesuggestor < backups/pre-phase2-backup.sql

# Wait for issues to be resolved, then retry activation
```

## Success Criteria

Phase 2 activation is successful when:

1. ✅ All template files activated without errors
2. ✅ Database migrations completed successfully
3. ✅ All tests passing (even if incomplete)
4. ✅ No breaking changes to Phase 1 functionality
5. ✅ Branch pushed and CI/CD pipeline passing
6. ✅ Ready for feature implementation

## Next Steps After Activation

1. Implement FavoritesRepository methods
2. Implement WatchLaterRepository methods
3. Implement RatingRepository methods
4. Implement FilterBuilder logic
5. Write comprehensive tests
6. Create UI components for new features
7. Update existing pages to use new features
8. Performance testing and optimization

---

**Ready to activate? Execute steps 1-10 in order when Judge shows GREEN!**
