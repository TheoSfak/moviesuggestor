#!/bin/bash
# Phase 2 Activation Script
# Run this script ONLY after Judge approval (GREEN status)

set -e  # Exit on any error

echo "======================================"
echo "Phase 2 Activation Script"
echo "======================================"
echo ""

# Check if Judge approved (manual confirmation)
read -p "Has Judge approved Phase 1 with GREEN status? (yes/no): " APPROVED
if [ "$APPROVED" != "yes" ]; then
    echo "❌ Aborting: Phase 1 must be approved before activating Phase 2"
    exit 1
fi

echo ""
echo "✓ Starting Phase 2 activation..."
echo ""

# Step 1: Ensure on main branch
echo "Step 1: Checking out main branch..."
git checkout main
git pull origin main

# Step 2: Create Phase 2 branch
echo "Step 2: Creating phase2-development branch..."
git checkout -b phase2-development

# Step 3: Create directories
echo "Step 3: Creating directories..."
mkdir -p migrations

# Step 4: Copy templates to active locations
echo "Step 4: Copying template files..."
cp phase2-prep/templates/FavoritesRepository.php.template src/FavoritesRepository.php.inactive
cp phase2-prep/templates/WatchLaterRepository.php.template src/WatchLaterRepository.php.inactive
cp phase2-prep/templates/RatingRepository.php.template src/RatingRepository.php.inactive
cp phase2-prep/templates/FilterBuilder.php.template src/FilterBuilder.php.inactive

# Step 5: Copy migrations
echo "Step 5: Copying migration files..."
for file in phase2-prep/migrations/*.template; do
    basename=$(basename "$file" .template)
    cp "$file" "migrations/$basename.inactive"
done

# Step 6: Copy test stubs
echo "Step 6: Copying test stubs..."
for file in phase2-prep/tests/*.stub; do
    basename=$(basename "$file" .stub)
    cp "$file" "tests/$basename.inactive"
done

# Step 7: Update .gitignore
echo "Step 7: Backing up and updating .gitignore..."
cp .gitignore .gitignore.phase1.backup
cp phase2-prep/.gitignore .gitignore

echo ""
echo "======================================"
echo "✅ Phase 2 scaffolding activated!"
echo "======================================"
echo ""
echo "NEXT STEPS (Manual):"
echo "1. Uncomment code in src/*Repository.php.inactive files"
echo "2. Rename .inactive files to .php (remove .inactive extension)"
echo "3. Uncomment SQL in migrations/*.sql.inactive files"
echo "4. Rename .inactive to .sql"
echo "5. Run: php migrations/run-migrations.php"
echo "6. Activate tests: rename .inactive to .php in tests/"
echo "7. Run: ./vendor/bin/phpunit"
echo "8. Commit: git add . && git commit -m 'Phase 2: Activate scaffolding'"
echo "9. Push: git push -u origin phase2-development"
echo ""
echo "See phase2-prep/ACTIVATION_CHECKLIST.md for detailed steps"
