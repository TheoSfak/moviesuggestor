# Phase 2 Frontend - Quick Start Guide

## 🚀 Quick Testing Steps

### Prerequisites
✅ Phase 2 database migrations completed
✅ Composer dependencies installed
✅ Web server running (Apache/Nginx with PHP 8.0+)

### Step 1: Verify Files
```powershell
# Check that new files exist
Test-Path index.php      # Should be TRUE (updated)
Test-Path api.php        # Should be TRUE (new)
Test-Path src/FavoritesRepository.php
Test-Path src/WatchLaterRepository.php
Test-Path src/RatingRepository.php
Test-Path src/FilterBuilder.php
```

### Step 2: Access Application
```
http://localhost/moviesuggestor/
```

### Step 3: Test Each Feature

#### 🎬 Advanced Filtering
1. **Multi-Category:**
   - Hold Ctrl/Cmd and click multiple categories
   - Click "Search Movies"
   - Verify only selected categories appear

2. **Year Range:**
   - Enter "2000" in "Year From"
   - Enter "2020" in "Year To"
   - Click "Search Movies"
   - Verify movies are from 2000-2020

3. **Text Search:**
   - Type "love" in Search Text
   - Click "Search Movies"
   - Verify results contain "love" in title/description

4. **Combined Filters:**
   - Select Action + Sci-Fi categories
   - Set min score: 7.0
   - Set year range: 2010-2024
   - Type "space"
   - Click "Search Movies"
   - Verify all filters apply

#### ❤️ Favorites
1. Click ❤️ button on any movie
2. Button should turn red and say "Favorited"
3. Refresh page
4. Button should still be red (persisted)
5. Click again to remove
6. Button should turn white

#### 📌 Watch Later
1. Click 📌 button on any movie
2. Button should turn blue and say "In List"
3. Refresh page
4. Button should still be blue
5. Click again to remove

#### ⭐ Ratings
1. Click any star (1-5) on a movie
2. Stars should fill up to that point
3. "Your rating: X/10" should appear
4. Refresh page
5. Rating should persist
6. Click different star to update

#### 📄 Pagination
1. Scroll to bottom
2. Click "Next ›"
3. Should show next 12 movies
4. Click page number
5. Should jump to that page
6. Click "« First"
7. Should return to page 1

### Step 4: Browser Console Check
```
F12 → Console Tab
Should see NO errors
Should see successful API responses when clicking actions
```

### Step 5: Network Tab Check
```
F12 → Network Tab
Click a favorite button
Should see POST to api.php
Response should be: {"success":true,"message":"..."}
```

---

## 🐛 Troubleshooting

### Issue: Favorites/Watch Later/Ratings don't work
**Solution:**
1. Check browser console for errors
2. Verify api.php exists and is accessible
3. Test direct access: http://localhost/moviesuggestor/api.php
4. Check PHP error logs

### Issue: "User not authenticated" error
**Solution:**
1. Clear browser cookies
2. Restart browser
3. Check that session_start() is working

### Issue: Filters don't work
**Solution:**
1. Verify FilterBuilder.php exists
2. Check that Phase 2 schema includes year column
3. Run migrations again if needed

### Issue: Stars don't appear
**Solution:**
1. Check browser console for JavaScript errors
2. Verify rateMovie() function exists
3. Check that ratings table exists

### Issue: Pagination shows wrong count
**Solution:**
1. Clear any old URL parameters
2. Reset filters
3. Check that movies exist in database

---

## 🎨 Customization Examples

### Change Movies Per Page
In `index.php` line ~26:
```php
$perPage = 12;  // Change to 6, 12, 24, etc.
```

### Change Color Scheme
In `index.php` CSS section:
```css
/* Purple gradient → Blue gradient */
background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
/* Change to */
background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
```

### Change Toast Duration
In `index.php` JavaScript:
```javascript
showMessage(message, duration = 3000)  // 3 seconds
// Change to 5 seconds:
showMessage(message, duration = 5000)
```

### Add More Filter Options
1. Add input field in filters section
2. Add GET parameter handling in PHP
3. Add condition to FilterBuilder
4. Update URL in JavaScript if using AJAX

---

## 📊 Testing Matrix

| Feature | Desktop | Tablet | Mobile | Status |
|---------|---------|--------|--------|--------|
| Multi-category filter | ✅ | ✅ | ✅ | Ready |
| Year range filter | ✅ | ✅ | ✅ | Ready |
| Text search | ✅ | ✅ | ✅ | Ready |
| Favorites | ✅ | ✅ | ✅ | Ready |
| Watch Later | ✅ | ✅ | ✅ | Ready |
| Ratings | ✅ | ✅ | ✅ | Ready |
| Pagination | ✅ | ✅ | ✅ | Ready |
| Responsive UI | ✅ | ✅ | ✅ | Ready |

---

## 🔍 Manual Test Script

### Test #1: Fresh Page Load
```
1. Open http://localhost/moviesuggestor/
2. Verify movies display in grid
3. Verify user info shows in header
4. Verify filters are present
5. Verify pagination shows at bottom
✅ PASS if all visible and no errors
```

### Test #2: Filter Interaction
```
1. Select "Action" category
2. Set min score to 7.0
3. Click "Search Movies"
4. Verify URL updates with parameters
5. Verify only Action movies with score ≥7.0 show
✅ PASS if correct movies display
```

### Test #3: AJAX Operations
```
1. Click ❤️ on first movie
2. Check console - should see POST request
3. Check button - should turn red
4. Click again
5. Should turn white
✅ PASS if toggle works without page reload
```

### Test #4: Persistence
```
1. Add 3 movies to favorites
2. Rate 2 movies
3. Add 1 to watch later
4. Refresh page (F5)
5. Verify all actions persisted
✅ PASS if state maintained after refresh
```

### Test #5: Mobile Responsive
```
1. Open DevTools (F12)
2. Toggle device toolbar (Ctrl+Shift+M)
3. Select iPhone 12 Pro
4. Verify layout stacks vertically
5. Verify buttons are tappable
6. Test all features work on mobile
✅ PASS if usable on mobile
```

---

## 🎯 Performance Benchmarks

### Expected Load Times
- **First page load:** < 1 second
- **Filter application:** < 500ms
- **AJAX action:** < 200ms
- **Page navigation:** < 500ms

### Database Queries Per Request
- **Main page:** 4-6 queries
  - Get categories
  - Get movies (paginated)
  - Get user favorites
  - Get user watch later
  - Get user ratings (per movie)
- **AJAX action:** 1-2 queries

---

## ✅ Production Checklist

Before deploying to production:

- [ ] Replace demo user session with real authentication
- [ ] Add CSRF tokens to forms
- [ ] Enable HTTPS only
- [ ] Set proper session cookie settings (secure, httponly)
- [ ] Configure error_log to file, not screen
- [ ] Add rate limiting to API endpoints
- [ ] Optimize database indexes
- [ ] Enable production caching
- [ ] Test with real user accounts
- [ ] Add monitoring/analytics
- [ ] Create backup procedures
- [ ] Write API documentation
- [ ] Add user terms of service
- [ ] Test with various browsers
- [ ] Run security audit
- [ ] Load test with concurrent users

---

## 📞 Next Steps

After testing:
1. ✅ If everything works → Deploy to staging
2. ⚠️ If issues found → Check troubleshooting section
3. 🎉 When stable → Deploy to production
4. 📈 Monitor usage and performance
5. 🔄 Iterate based on user feedback

---

**Happy Testing! 🚀**

Generated: January 20, 2026
