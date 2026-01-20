# TMDB Integration Setup Guide

## Overview
The Movie Suggestor application now integrates with The Movie Database (TMDB) API to search and import movies from their extensive catalog of over 800,000 movies.

## Features Implemented

### 1. **Online Movie Search** 🌐
- Search TMDB's database by movie title
- Browse popular movies
- Greek language support (movie descriptions in Greek when available)
- View movie posters, ratings, release years, and descriptions

### 2. **One-Click Import** 💾
- Import movies directly to your local database
- Automatic data mapping:
  - TMDB genres → Greek categories
  - Movie metadata (director, actors, runtime)
  - High-quality poster and backdrop URLs
  - TMDB ratings
  - YouTube trailer links

### 3. **Intelligent Genre Mapping**
TMDB genres are automatically mapped to Greek categories:
- Drama → Δράμα
- Comedy → Κωμωδία
- Action → Δράση
- Adventure → Περιπέτεια
- Romance → Ρομαντική
- Thriller → Θρίλερ
- Horror → Τρόμου
- Crime → Αστυνομική
- Fantasy → Φαντασίας
- Sci-Fi → Επιστημονική Φαντασία
- Animation → Κινουμένων Σχεδίων
- And more...

## Files Created

### Backend Files
1. **src/TMDBService.php** - TMDB API integration service
   - No external dependencies (pure PHP)
   - Uses cURL or file_get_contents
   - Comprehensive error handling
   - Rate limiting awareness

2. **api/tmdb-search.php** - Search endpoint
   - Handles movie searches
   - Popular movies endpoint
   - Discover movies with filters

3. **api/import-movie.php** - Import endpoint
   - Imports TMDB movies to local database
   - Prevents duplicates
   - Fetches full movie details

### Frontend Updates
4. **index.php** - Updated UI
   - New TMDB search section above local filters
   - Real-time AJAX search
   - Movie cards with posters
   - One-click import buttons
   - Pagination for search results

### Configuration
5. **.env.example** - Updated with TMDB configuration

## Setup Instructions

### Step 1: Get Your TMDB API Key (FREE)

1. **Create Account**
   - Go to: https://www.themoviedb.org/signup
   - Sign up for a free account

2. **Request API Key**
   - Log in to your account
   - Go to: https://www.themoviedb.org/settings/api
   - Click "Create" under "Request an API Key"
   - Choose "Developer"

3. **Fill Application Form**
   ```
   Application Name: Movie Suggestor
   Application URL: http://localhost (or your domain)
   Application Summary: Personal movie recommendation system
   ```

4. **Copy Your API Key**
   - Once approved (instant for developer keys), you'll see:
     - API Key (v3 auth) - **Copy this one**
     - API Read Access Token (v4 auth) - Not needed

### Step 2: Configure Environment

1. **Copy .env.example to .env**
   ```powershell
   # In workspace directory
   cd C:\Users\user\Desktop\moviesuggestor
   Copy-Item .env.example .env
   ```

2. **Edit .env file**
   ```powershell
   notepad .env
   ```

3. **Add your TMDB API key**
   ```env
   TMDB_API_KEY=your_actual_api_key_here
   ```

4. **Save and close**

### Step 3: Set Environment Variable (Windows)

**Option A: For Current PowerShell Session Only**
```powershell
$env:TMDB_API_KEY = "your_api_key_here"
```

**Option B: System-Wide (Permanent)**
```powershell
# Run PowerShell as Administrator
[System.Environment]::SetEnvironmentVariable('TMDB_API_KEY', 'your_api_key_here', 'User')

# Restart Apache to pick up the environment variable
net stop Apache2.4
net start Apache2.4
```

**Option C: Apache httpd.conf**
```apache
# Edit C:\xampp\apache\conf\httpd.conf
# Add this line:
SetEnv TMDB_API_KEY your_api_key_here

# Restart Apache
```

### Step 4: Copy Files to Web Server

```powershell
# Copy all files to web server directory
robocopy C:\Users\user\Desktop\moviesuggestor C:\xampp\htdocs\moviesuggestor /E /XD vendor .git node_modules
```

### Step 5: Test the Integration

1. **Restart Apache**
   ```powershell
   net stop Apache2.4
   net start Apache2.4
   ```

2. **Open Application**
   ```
   http://localhost/moviesuggestor/
   ```

3. **Test TMDB Search**
   - Look for the "🌐 Search Online Movies (TMDB)" section
   - Try searching for "The Matrix"
   - Click "🔥 Popular" to see trending movies
   - Click "💾 Import to Database" on any movie

## Usage Guide

### Searching Movies
1. **By Title**
   - Enter movie name in search box
   - Click "🔍 Search TMDB"
   - Browse results with pagination

2. **Popular Movies**
   - Click "🔥 Popular" button
   - See currently trending movies
   - No search query needed

### Importing Movies
1. Click "💾 Import to Database" on any TMDB result
2. Movie is imported with:
   - Title, description, rating
   - Release year
   - Director and actors
   - Poster and backdrop images
   - YouTube trailer (if available)
   - Greek category
3. Imported movies appear in your local database immediately
4. Page reloads to show updated local movies

### Keyboard Shortcuts
- `Ctrl+T` - Focus TMDB search box
- `Ctrl+K` - Focus local search box
- `Enter` - Submit TMDB search

## API Rate Limits

TMDB API limits:
- **Free Tier**: 40 requests per 10 seconds
- **Daily**: Thousands of requests (very generous)

The service handles rate limiting gracefully with error messages.

## Troubleshooting

### "TMDB API not configured" Error
**Solution**: Set the TMDB_API_KEY environment variable
```powershell
$env:TMDB_API_KEY = "your_key"
# Then restart Apache
```

### "Invalid API key" Error
**Solution**: 
- Verify your API key is correct
- Ensure you're using the v3 API key (not v4 token)
- Check for extra spaces or quotes

### Movies Not Importing
**Causes**:
1. Movie already exists (check by title)
2. Database connection issues
3. Missing table columns (run migrations)

**Solution**:
```powershell
# Check database schema
cd C:\xampp\htdocs\moviesuggestor
php validate-db.php
```

### No Results from Search
**Causes**:
1. Network connectivity issues
2. TMDB service temporarily down
3. Invalid search query

**Solution**:
- Check internet connection
- Try different search terms
- Use "Popular" button instead

### Images Not Loading
**Cause**: TMDB image URLs require internet connection

**Solution**: 
- Ensure internet connectivity
- Check browser console for errors
- TMDB uses CDN (https://image.tmdb.org)

## Technical Details

### API Endpoints Used
- `GET /search/movie` - Search movies by title
- `GET /movie/popular` - Get popular movies
- `GET /discover/movie` - Discover with filters
- `GET /movie/{id}` - Get detailed movie info

### Data Flow
```
User Search → api/tmdb-search.php → TMDBService → TMDB API
                                  ↓
                          Return JSON Results
                                  ↓
                     JavaScript Renders Cards
                                  ↓
User Clicks Import → api/import-movie.php → Get Full Details → Insert DB
```

### Security Features
- API key stored in environment (not in code)
- Input sanitization
- SQL injection protection (prepared statements)
- XSS prevention (htmlspecialchars)
- HTTPS for TMDB API calls
- Error messages don't expose sensitive info

## Advanced Configuration

### Change Language
Edit `src/TMDBService.php`:
```php
private const DEFAULT_LANGUAGE = 'en-US'; // Change from 'el-GR'
```

### Customize Genre Mapping
Edit `GENRE_MAP` array in `src/TMDBService.php`

### Adjust Image Sizes
Available sizes:
- Posters: w92, w154, w185, w342, w500, w780, original
- Backdrops: w300, w780, w1280, original

## Resources

- **TMDB API Docs**: https://developers.themoviedb.org/3
- **Get API Key**: https://www.themoviedb.org/settings/api
- **API Status**: https://status.themoviedb.org/
- **TMDB Forum**: https://www.themoviedb.org/talk

## Support

For issues or questions:
1. Check error logs: `C:\xampp\apache\logs\error.log`
2. Check PHP errors: Enable `display_errors` in php.ini
3. Verify API key is set: `echo $env:TMDB_API_KEY` (PowerShell)

## Future Enhancements

Possible additions:
- [ ] Advanced filters (genre, year, rating)
- [ ] Cast and crew details
- [ ] Similar movie recommendations
- [ ] Movie reviews from TMDB
- [ ] Watchlist sync with TMDB account
- [ ] Multi-language support toggle
- [ ] Batch import functionality
- [ ] TMDB trending/upcoming sections

## License

TMDB API is free for non-commercial use. Commercial use requires approval.
Attribution: "This product uses the TMDB API but is not endorsed or certified by TMDB."

---

**Status**: ✅ Fully Implemented and Ready to Use
**Version**: 1.0.0
**Last Updated**: January 2026
