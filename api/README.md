# Movie Suggestor API - Phase 2

RESTful API endpoints for managing user interactions with movies.

## 📁 Directory Structure

```
api/
├── index.php          # API documentation (HTML)
├── favorites.php      # Favorites endpoint
├── watch-later.php    # Watch later endpoint
├── ratings.php        # Ratings endpoint
├── .htaccess          # Apache configuration
└── README.md          # This file
```

## 🚀 Quick Start

### Access API Documentation

Open in browser:
```
http://localhost/moviesuggestor/api/
```

### Example Requests

#### Add to Favorites
```bash
curl -X POST http://localhost/moviesuggestor/api/favorites.php \
  -H "Content-Type: application/json" \
  -d '{"user_id": 1, "movie_id": 42}'
```

#### Get Watch Later List
```bash
curl "http://localhost/moviesuggestor/api/watch-later.php?user_id=1"
```

#### Rate a Movie
```bash
curl -X POST http://localhost/moviesuggestor/api/ratings.php \
  -H "Content-Type: application/json" \
  -d '{"user_id": 1, "movie_id": 42, "rating": 8.5, "review": "Great movie!"}'
```

## 📋 Endpoints Overview

### Favorites (`/api/favorites.php`)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `?user_id={id}` | List user's favorites |
| POST | - | Add to favorites |
| DELETE | - | Remove from favorites |

### Watch Later (`/api/watch-later.php`)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `?user_id={id}[&include_watched=1]` | List watch later movies |
| POST | - | Add to watch later |
| PATCH | - | Mark as watched |
| DELETE | - | Remove from watch later |

### Ratings (`/api/ratings.php`)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `?user_id={id}&movie_id={id}` | Get user's rating |
| GET | `?movie_id={id}` | Get movie statistics |
| POST | - | Add new rating |
| PUT | - | Update rating |
| DELETE | - | Delete rating |

## 🔒 Security Features

- **Input Validation**: All inputs are validated before processing
- **Prepared Statements**: SQL injection protection via PDO prepared statements
- **Error Handling**: Graceful error handling with appropriate HTTP status codes
- **CORS Support**: Configured for cross-origin requests
- **Type Checking**: Strict type validation for all parameters
- **Error Logging**: Errors logged server-side, generic messages to clients

## 📝 Response Format

### Success Response
```json
{
  "success": true,
  "data": {
    // Response data
  }
}
```

### Error Response
```json
{
  "success": false,
  "error": "Error message"
}
```

## 🔢 HTTP Status Codes

| Code | Description |
|------|-------------|
| 200 | Success |
| 201 | Resource created |
| 204 | No content (OPTIONS) |
| 400 | Bad request |
| 404 | Not found |
| 405 | Method not allowed |
| 409 | Conflict (duplicate) |
| 500 | Server error |
| 501 | Not implemented |

## 🔧 Configuration

### PHP Requirements
- PHP 7.4 or higher
- PDO extension with MySQL driver
- JSON extension

### Database
Uses the repository classes from `/src/`:
- `FavoritesRepository.php`
- `WatchLaterRepository.php`
- `RatingRepository.php`

### Environment Variables
Database connection can be configured via environment variables:
- `DB_HOST` - Database host (default: localhost)
- `DB_PORT` - Database port (default: 3306)
- `DB_NAME` - Database name (default: moviesuggestor)
- `DB_USER` - Database username (default: root)
- `DB_PASS` - Database password (default: empty)

## 🧪 Testing

### Using cURL

**Test Favorites:**
```bash
# Add favorite
curl -X POST http://localhost/moviesuggestor/api/favorites.php \
  -H "Content-Type: application/json" \
  -d '{"user_id": 1, "movie_id": 1}'

# Get favorites
curl "http://localhost/moviesuggestor/api/favorites.php?user_id=1"

# Remove favorite
curl -X DELETE http://localhost/moviesuggestor/api/favorites.php \
  -H "Content-Type: application/json" \
  -d '{"user_id": 1, "movie_id": 1}'
```

**Test Watch Later:**
```bash
# Add to watch later
curl -X POST http://localhost/moviesuggestor/api/watch-later.php \
  -H "Content-Type: application/json" \
  -d '{"user_id": 1, "movie_id": 1}'

# Mark as watched
curl -X PATCH http://localhost/moviesuggestor/api/watch-later.php \
  -H "Content-Type: application/json" \
  -d '{"user_id": 1, "movie_id": 1}'

# Get watch later list
curl "http://localhost/moviesuggestor/api/watch-later.php?user_id=1"
```

**Test Ratings:**
```bash
# Add rating
curl -X POST http://localhost/moviesuggestor/api/ratings.php \
  -H "Content-Type: application/json" \
  -d '{"user_id": 1, "movie_id": 1, "rating": 8.5, "review": "Great!"}'

# Update rating
curl -X PUT http://localhost/moviesuggestor/api/ratings.php \
  -H "Content-Type: application/json" \
  -d '{"user_id": 1, "movie_id": 1, "rating": 9.0, "review": "Amazing!"}'

# Get movie stats
curl "http://localhost/moviesuggestor/api/ratings.php?movie_id=1"

# Delete rating
curl -X DELETE http://localhost/moviesuggestor/api/ratings.php \
  -H "Content-Type: application/json" \
  -d '{"user_id": 1, "movie_id": 1}'
```

### Using JavaScript (Fetch API)

```javascript
// Add to favorites
fetch('http://localhost/moviesuggestor/api/favorites.php', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({
    user_id: 1,
    movie_id: 42
  })
})
.then(response => response.json())
.then(data => console.log(data));
```

## 📚 Integration Examples

### jQuery
```javascript
// Add to favorites
$.ajax({
  url: '/api/favorites.php',
  method: 'POST',
  contentType: 'application/json',
  data: JSON.stringify({
    user_id: 1,
    movie_id: 42
  }),
  success: function(response) {
    console.log('Success:', response);
  },
  error: function(xhr) {
    console.error('Error:', xhr.responseJSON);
  }
});
```

### Axios
```javascript
// Rate a movie
axios.post('/api/ratings.php', {
  user_id: 1,
  movie_id: 42,
  rating: 8.5,
  review: 'Great movie!'
})
.then(response => {
  console.log('Success:', response.data);
})
.catch(error => {
  console.error('Error:', error.response.data);
});
```

## 🐛 Troubleshooting

### Common Issues

**CORS Errors:**
- Ensure `.htaccess` is being read by Apache
- Check that `mod_headers` is enabled
- Verify CORS headers in PHP files

**Database Connection Errors:**
- Verify database credentials
- Check that MySQL is running
- Ensure database and tables exist

**404 Not Found:**
- Check Apache configuration
- Verify file permissions
- Ensure `mod_rewrite` is enabled if using URL rewriting

**JSON Parse Errors:**
- Verify Content-Type header is set
- Check JSON syntax in request body
- Ensure PHP input stream is readable

## 📖 API Design Principles

1. **RESTful**: Uses proper HTTP methods and status codes
2. **JSON-First**: All requests and responses use JSON
3. **Stateless**: Each request contains all necessary information
4. **Consistent**: Uniform response format across all endpoints
5. **Secure**: Input validation, prepared statements, error handling
6. **Documented**: Comprehensive inline documentation and API docs

## 🔄 Version History

- **v1.0.0** (2026-01-20): Initial Phase 2 implementation
  - Favorites endpoint
  - Watch later endpoint
  - Ratings endpoint
  - API documentation
  - CORS support

## 📧 Support

For issues or questions, refer to the main project documentation or check the error logs.

## 📄 License

Part of the Movie Suggestor project.
