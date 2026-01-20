# 🎬 TMDB Integration - Quick Start Guide

## ✅ Files Created

### Backend (PHP)
- ✓ `src/TMDBService.php` - TMDB API service (no dependencies, pure PHP)
- ✓ `api/tmdb-search.php` - Search endpoint
- ✓ `api/import-movie.php` - Import movies to database
- ✓ `test-tmdb.php` - Test script

### Frontend
- ✓ `index.php` - Updated with TMDB search UI

### Documentation
- ✓ `TMDB_SETUP.md` - Comprehensive setup guide
- ✓ `.env.example` - Updated with TMDB configuration

## 🚀 Quick Setup (3 Steps)

### Step 1: Get Free TMDB API Key
1. Go to: https://www.themoviedb.org/signup
2. Create free account
3. Visit: https://www.themoviedb.org/settings/api
4. Click "Create" → Choose "Developer"
5. Copy your **API Key (v3 auth)**

### Step 2: Set Environment Variable
```powershell
# Set for current session
$env:TMDB_API_KEY = "paste_your_key_here"

# OR set permanently (PowerShell as Admin)
[System.Environment]::SetEnvironmentVariable('TMDB_API_KEY', 'your_key', 'User')
```

### Step 3: Restart Apache
```powershell
net stop Apache2.4
net start Apache2.4
```

## ✅ Test Installation

```powershell
cd C:\xampp\htdocs\moviesuggestor
php test-tmdb.php
```

Expected output:
```
===========================================
TMDB Integration Test
===========================================

1. Checking API Key Configuration...
   ✓ API Key found: 1234567...

2. Initializing TMDB Service...
   ✓ Service initialized successfully

3. Testing Movie Search (The Matrix)...
   ✓ Search successful - Found 20 results
   ...

✓ All Tests Passed!
```

## 🎯 How to Use

1. **Open Application**
   ```
   http://localhost/moviesuggestor/
   ```

2. **Search for Movies**
   - Look for "🌐 Search Online Movies (TMDB)" section at top
   - Enter movie name (e.g., "Inception")
   - Click "🔍 Search TMDB"

3. **Browse Popular Movies**
   - Click "🔥 Popular" button
   - See trending movies

4. **Import Movies**
   - Click "💾 Import to Database" on any result
   - Movie is added to your local database with:
     - Greek category
     - Poster images
     - Director & actors
     - Trailer link
     - Ratings

## 🎨 Features

### Greek Language Support
- Movie descriptions in Greek (when available)
- Genre mapping to Greek categories:
  - Drama → Δράμα
  - Comedy → Κωμωδία
  - Action → Δράση
  - Romance → Ρομαντική
  - And 15+ more...

### Smart Import
- Prevents duplicate movies
- Fetches complete movie details
- Maps TMDB data to local schema
- Includes high-quality images

### User-Friendly UI
- Search results with movie posters
- Pagination for browsing
- One-click import
- Real-time feedback
- Keyboard shortcuts: `Ctrl+T` to search

## 🔧 Troubleshooting

### "TMDB API not configured" Error
**Fix:**
```powershell
# Check if set
echo $env:TMDB_API_KEY

# Set it
$env:TMDB_API_KEY = "your_key_here"

# Restart Apache
net stop Apache2.4; net start Apache2.4
```

### "Invalid API key" Error
- Verify you copied the correct key (v3, not v4)
- Check for extra spaces
- Make sure you're using API Key, not Access Token

### Can't Find Search Section
- Clear browser cache
- Ensure index.php was updated
- Check that files were copied to both locations

## 📁 File Locations

### Workspace (Development)
```
C:\Users\user\Desktop\moviesuggestor\
├── src/TMDBService.php
├── api/tmdb-search.php
├── api/import-movie.php
├── index.php (updated)
├── test-tmdb.php
└── TMDB_SETUP.md
```

### Web Server (Production)
```
C:\xampp\htdocs\moviesuggestor\
├── src/TMDBService.php
├── api/tmdb-search.php
├── api/import-movie.php
├── index.php (updated)
├── test-tmdb.php
└── TMDB_SETUP.md
```

## 📊 What Gets Imported

When you import a movie from TMDB:

| Field | Source | Example |
|-------|--------|---------|
| Title | TMDB title | "The Matrix" |
| Category | Mapped from genres | "Επιστημονική Φαντασία" |
| Score | TMDB vote_average | 8.7 |
| Release Year | TMDB release_date | 1999 |
| Director | TMDB credits | "Lana Wachowski" |
| Actors | TMDB cast (top 5) | "Keanu Reeves, Laurence Fishburne..." |
| Runtime | TMDB runtime | 136 minutes |
| Description | TMDB overview | Full synopsis |
| Poster URL | TMDB poster_path | High-res image URL |
| Backdrop URL | TMDB backdrop_path | Banner image URL |
| Trailer URL | TMDB videos | YouTube link |
| Rating | TMDB vote_average | Same as score |

## 🌟 API Limits

TMDB Free Tier:
- ✓ 40 requests per 10 seconds
- ✓ Thousands per day
- ✓ More than enough for personal use

## 📚 Resources

- **API Key**: https://www.themoviedb.org/settings/api
- **Full Guide**: `TMDB_SETUP.md`
- **API Docs**: https://developers.themoviedb.org/3
- **TMDB Status**: https://status.themoviedb.org/

## 🎉 You're Ready!

Everything is set up and ready to use. Just:
1. Get your API key
2. Set the environment variable
3. Restart Apache
4. Start searching movies!

---

**Need Help?** Check `TMDB_SETUP.md` for detailed troubleshooting.
