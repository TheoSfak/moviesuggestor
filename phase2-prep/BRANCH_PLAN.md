# Phase 2 Branch Strategy

## Branch Structure

```
main (Phase 1 - approved by Judge)
  │
  └─── phase2-development (create after Judge approval)
         │
         ├─── phase2-database (database migrations)
         ├─── phase2-repositories (new repository classes)
         ├─── phase2-ui (enhanced UI components)
         └─── phase2-testing (comprehensive test coverage)
```

## Branch Creation Commands (Run AFTER Judge approval)

### 1. Create Phase 2 Development Branch
```bash
git checkout main
git pull origin main
git checkout -b phase2-development
```

### 2. Create Feature Branches
```bash
# Database migrations
git checkout -b phase2-database phase2-development

# Repository classes
git checkout -b phase2-repositories phase2-development

# UI enhancements
git checkout -b phase2-ui phase2-development

# Testing
git checkout -b phase2-testing phase2-development
```

## Merge Strategy

1. **Feature branches** → merge into **phase2-development**
2. Test and validate **phase2-development**
3. **phase2-development** → merge into **main** when stable

## Activation Checklist

- [ ] Judge approves Phase 1 (GREEN status)
- [ ] Create phase2-development branch
- [ ] Copy templates from phase2-prep/ to src/
- [ ] Run database migrations
- [ ] Activate test scaffolding
- [ ] Update composer.json if needed
- [ ] Run full test suite
- [ ] Update README for Phase 2

## Git Protection Rules (Recommended)

```bash
# Protect main branch - require PR reviews
# Protect phase2-development - require tests to pass
# Allow force-push on feature branches only
```

## Quick Start After Approval

```bash
# 1. Ensure Phase 1 is merged and approved
git checkout main
git pull origin main

# 2. Create Phase 2 branch
git checkout -b phase2-development

# 3. Copy prepared files
cp -r phase2-prep/templates/* src/
cp -r phase2-prep/migrations/* migrations/
cp -r phase2-prep/tests/* tests/

# 4. Run migrations
php migrations/run-migrations.php

# 5. First commit
git add .
git commit -m "Phase 2: Initialize scaffolding and migrations"
git push -u origin phase2-development
```
