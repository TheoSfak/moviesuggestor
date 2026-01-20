# Phase 2 Quick Reference Card

**Movie Suggestor - Phase 2 Implementation Guide**  
**Print this for your desk! 📋**

---

## 📊 Phase 2 By The Numbers

```
┌────────────────────────────────────────┐
│  NEW FEATURES:        15+              │
│  NEW CLASSES:         7                │
│  NEW METHODS:         25+              │
│  NEW TESTS:           100+             │
│  NEW DB TABLES:       4                │
│  NEW DB COLUMNS:      11               │
│  TIMELINE:            5 weeks          │
│  TARGET COVERAGE:     90%+             │
│  TOTAL DOCS:          5 files          │
│  TOTAL PAGES:         ~66 pages        │
└────────────────────────────────────────┘
```

---

## 🗺️ Week-by-Week Roadmap

| Week | Focus Area | Key Tasks | Tests |
|------|-----------|-----------|-------|
| **1** | Database & Core | Schema, SessionManager, FilterBuilder | 25+ |
| **2** | User Features | Favorites, WatchLater, Ratings | 30+ |
| **3** | Filtering UI | Multi-filter, search, sort | 20+ |
| **4** | Admin & Polish | CRUD panel, details page | 15+ |
| **5** | Final Testing | Integration, security, docs | 10+ |

---

## 🎯 Daily Checklist Template

```
[ ] Pull latest code
[ ] Review today's tasks from PHASE2_CHECKLIST.md
[ ] Write tests FIRST (TDD)
[ ] Implement feature
[ ] Run PHPUnit - all tests pass?
[ ] Run PHPStan - no errors?
[ ] Test manually in browser
[ ] Update PHASE2_CHECKLIST.md
[ ] Commit with descriptive message
[ ] Push to GitHub
```

---

## 🗄️ New Database Tables

```sql
favorites (session_id, movie_id)
watch_later (session_id, movie_id, watched)
user_ratings (session_id, movie_id, rating)
genres + movie_genres (multi-genre support)
```

**Enhanced movies table**: +11 columns (year, director, posters, ratings, etc.)

---

## 🏗️ New PHP Classes

```
src/
├── FilterBuilder.php       (Complex query building)
├── FavoritesRepository.php (Favorites management)
├── WatchLaterRepository.php (Watch list management)
├── RatingRepository.php    (User ratings)
├── SessionManager.php      (Session handling)
├── Paginator.php           (Pagination logic)
└── MovieValidator.php      (Input validation)
```

---

## 🧪 Testing Breakdown

```
Unit Tests:
├── FilterBuilderTest.php         (15 tests)
├── FavoritesRepositoryTest.php   (10 tests)
├── WatchLaterRepositoryTest.php  (10 tests)
├── RatingRepositoryTest.php      (10 tests)
├── PaginatorTest.php             (8 tests)
├── MovieValidatorTest.php        (12 tests)
├── SessionManagerTest.php        (5 tests)
└── MovieRepositoryTest.php       (20+ new tests)

Integration Tests:
└── EndToEndTest.php              (10+ tests)

TOTAL: 100+ tests
TARGET: 90%+ coverage
```

---

## 📄 New UI Pages

```
favorites.php      → View/manage favorite movies
watch-later.php    → Watch later list with watched tracking
movie.php          → Detailed single movie view
admin.php          → Admin CRUD panel
```

**Enhanced**: `index.php` with advanced filters

---

## 🔑 Key Features Summary

### Enhanced Filtering
✅ Multi-category (checkboxes)  
✅ Score range (min-max)  
✅ Year range  
✅ Text search  
✅ Sort options  

### User Interaction
✅ Favorites system  
✅ Watch later queue  
✅ User ratings (0-10)  
✅ Average rating display  

### UI/UX
✅ Enhanced cards  
✅ Loading states  
✅ Pagination  
✅ Mobile responsive  
✅ Smooth animations  

### Admin
✅ Add/Edit/Delete movies  
✅ Input validation  
✅ CSV import  

---

## 🚀 Commands Cheat Sheet

```bash
# Run all tests
vendor/bin/phpunit

# Run specific test file
vendor/bin/phpunit tests/FilterBuilderTest.php

# Run with coverage
vendor/bin/phpunit --coverage-html coverage

# Run PHPStan
vendor/bin/phpstan analyze

# Run PHP CS Fixer
vendor/bin/php-cs-fixer fix

# Start dev server
php -S localhost:8000

# Database migrations
mysql -u root -p moviesuggestor < migrations/001_add_movie_metadata.sql
mysql -u root -p moviesuggestor < migrations/002_create_favorites_table.sql
# ... etc
```

---

## ✅ Pre-Implementation Checklist

Before starting Phase 2:

```
[ ] Phase 1 Judge approved ⏳
[ ] Read PHASE2_SUMMARY.md
[ ] Read PHASE2_ROADMAP.md
[ ] Review PHASE2_DATABASE_SPEC.md
[ ] Print PHASE2_CHECKLIST.md
[ ] Backup production database
[ ] Create git branch: phase-2-development
[ ] Set up test database
[ ] Install any new dependencies
[ ] Team aligned on timeline
```

---

## 🎯 Success Criteria (Must Have)

```
[ ] All Phase 1 tests pass
[ ] All Phase 2 tests pass (100%)
[ ] 90%+ code coverage
[ ] No PHP errors/warnings
[ ] Works on Chrome, Firefox, Safari, Edge
[ ] Mobile responsive (320px+)
[ ] PHPStan level 6+ passes
[ ] Page loads < 2 seconds
[ ] All features documented
```

---

## ⚠️ Common Pitfalls to Avoid

1. **Don't break Phase 1** - Keep those tests passing!
2. **Don't skip tests** - Write tests FIRST (TDD)
3. **Don't hardcode** - Use configuration
4. **Don't forget validation** - Sanitize ALL inputs
5. **Don't over-engineer** - Keep it simple
6. **Don't commit broken code** - Test before push
7. **Don't ignore performance** - Use indexes
8. **Don't skip documentation** - Document as you go

---

## 🔐 Security Checklist (Phase 3 Prep)

```
[ ] Prepared statements for SQL (already done ✅)
[ ] htmlspecialchars for output (already done ✅)
[ ] CSRF tokens on forms
[ ] Session security (HttpOnly, Secure)
[ ] Input validation (length, type, range)
[ ] Rate limiting on actions
[ ] Content Security Policy headers
[ ] Custom error pages (no stack traces)
```

---

## 📚 Documentation Quick Links

- **Start Here**: [PHASE2_SUMMARY.md](PHASE2_SUMMARY.md)
- **Full Plan**: [PHASE2_ROADMAP.md](PHASE2_ROADMAP.md)
- **Daily Tracker**: [PHASE2_CHECKLIST.md](PHASE2_CHECKLIST.md)
- **Database**: [PHASE2_DATABASE_SPEC.md](PHASE2_DATABASE_SPEC.md)
- **Architecture**: [PHASE2_ARCHITECTURE.md](PHASE2_ARCHITECTURE.md)
- **Index**: [PHASE2_README.md](PHASE2_README.md)

---

## 📅 Timeline at a Glance

```
START
  │
  ├─ Week 1: Database & Core Classes
  │    └─ SessionManager, FilterBuilder, Enhanced MovieRepository
  │
  ├─ Week 2: User Features
  │    └─ Favorites, WatchLater, Ratings
  │
  ├─ Week 3: Enhanced Filtering
  │    └─ Multi-filter UI, Search, Sort
  │
  ├─ Week 4: Admin & Details
  │    └─ Admin panel, Movie details page
  │
  └─ Week 5: Testing & Documentation
       └─ Integration tests, Security tests, Final docs
END
```

**Total**: 5 weeks | **Target**: 90%+ coverage | **Tests**: 100+

---

## 🎓 Phase Requirements (JUDGE_RULES.md)

### Phase 2 Criteria (Already Met in Phase 1!)
✅ User can select movie category from dropdown  
✅ User can select minimum score  
✅ App returns matching movies  
✅ Empty results handled gracefully  
✅ Each movie has trailer link  

**Phase 2 adds**: Enhanced features to prepare for Phase 3!

### Phase 3 Preview (Coming Next)
- Edge cases tested
- SQL injection protection ✅ (already done)
- XSS protection ✅ (already done)
- All features have test coverage

---

## 💡 Tips for Success

1. **Test-Driven Development**: Write tests first!
2. **Small Commits**: Commit often with good messages
3. **Check Tests Often**: Run PHPUnit after each change
4. **Follow the Plan**: Stick to PHASE2_CHECKLIST.md
5. **Ask Questions**: Reference documentation
6. **Keep Phase 1 Green**: Don't break existing tests
7. **Document as You Go**: Update docs alongside code
8. **Review Before Push**: Manual testing in browser

---

## 🐛 Debugging Checklist

When something doesn't work:

```
[ ] Check PHP error log
[ ] Check browser console
[ ] Check network tab (AJAX calls)
[ ] Run PHPUnit tests
[ ] Run PHPStan
[ ] Check database connection
[ ] Verify database schema
[ ] Check query syntax
[ ] Verify variable types
[ ] Check file permissions
[ ] Clear browser cache
[ ] Restart PHP server
```

---

## 📊 Progress Tracking

Track your progress daily:

| Week | Mon | Tue | Wed | Thu | Fri | Tests | Coverage |
|------|-----|-----|-----|-----|-----|-------|----------|
| 1    | [ ] | [ ] | [ ] | [ ] | [ ] | __/25 | __%  |
| 2    | [ ] | [ ] | [ ] | [ ] | [ ] | __/30 | __%  |
| 3    | [ ] | [ ] | [ ] | [ ] | [ ] | __/20 | __%  |
| 4    | [ ] | [ ] | [ ] | [ ] | [ ] | __/15 | __%  |
| 5    | [ ] | [ ] | [ ] | [ ] | [ ] | __/10 | __%  |

**Total**: ___/100+ tests | Target: 90%+ coverage

---

## 🎯 Current Status

```
┌──────────────────────────────────────┐
│  PHASE 1:  ✅ Complete               │
│  JUDGE:    ⏳ Awaiting Approval      │
│  PHASE 2:  📋 Planning Done          │
│  STATUS:   ⏸️  Ready to Implement   │
└──────────────────────────────────────┘
```

**Next Action**: Wait for Phase 1 Judge approval  
**Then**: Begin Week 1, Day 1 from PHASE2_CHECKLIST.md  

---

## 🚦 Go/No-Go Decision

### ✅ GO when:
- Phase 1 Judge approves
- All docs reviewed
- Team ready
- Database backed up
- Timeline agreed

### 🛑 NO-GO if:
- Phase 1 needs fixes
- Unclear requirements
- Team not ready
- No database backup
- Resources unavailable

---

**Print this card and keep it visible during Phase 2 development!**

---

*Version 1.0 | Created: 2026-01-20 | Status: Ready ✅*
