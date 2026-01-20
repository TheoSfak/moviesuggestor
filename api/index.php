<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movie Suggestor API Documentation</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f5f5f5;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 20px;
            margin-bottom: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .subtitle {
            font-size: 1.2em;
            opacity: 0.9;
        }
        
        .intro {
            background: white;
            padding: 30px;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .endpoint {
            background: white;
            padding: 30px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .endpoint h2 {
            color: #667eea;
            margin-bottom: 15px;
            font-size: 1.8em;
        }
        
        .endpoint h3 {
            color: #764ba2;
            margin: 20px 0 10px;
            font-size: 1.3em;
        }
        
        .method {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 0.85em;
            margin-right: 10px;
            font-family: monospace;
        }
        
        .method.get { background: #61affe; color: white; }
        .method.post { background: #49cc90; color: white; }
        .method.put { background: #fca130; color: white; }
        .method.patch { background: #50e3c2; color: white; }
        .method.delete { background: #f93e3e; color: white; }
        
        .endpoint-url {
            font-family: monospace;
            background: #f8f9fa;
            padding: 10px 15px;
            border-radius: 4px;
            border-left: 4px solid #667eea;
            margin: 10px 0;
            overflow-x: auto;
        }
        
        .code-block {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            margin: 10px 0;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
        }
        
        .params-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        
        .params-table th,
        .params-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .params-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #667eea;
        }
        
        .params-table code {
            background: #f8f9fa;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 0.9em;
        }
        
        .response-codes {
            list-style: none;
            padding-left: 0;
        }
        
        .response-codes li {
            padding: 8px;
            margin: 5px 0;
            background: #f8f9fa;
            border-radius: 4px;
            border-left: 4px solid #667eea;
        }
        
        .response-codes code {
            font-weight: bold;
            color: #667eea;
        }
        
        .note {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        
        .note strong {
            color: #856404;
        }
        
        footer {
            text-align: center;
            padding: 30px;
            color: #666;
            margin-top: 40px;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>🎬 Movie Suggestor API</h1>
            <p class="subtitle">RESTful API for Phase 2 AJAX Operations</p>
        </header>
        
        <div class="intro">
            <h2>Overview</h2>
            <p>This API provides endpoints for managing user interactions with movies, including favorites, watch later lists, and ratings. All endpoints accept and return JSON data, use proper HTTP status codes, and support CORS for cross-origin requests.</p>
            
            <h3>Base URL</h3>
            <div class="endpoint-url">/api/</div>
            
            <h3>Response Format</h3>
            <p>All responses follow a consistent format:</p>
            <div class="code-block">{
  "success": true,
  "data": { ... }
}</div>
            
            <p>Error responses:</p>
            <div class="code-block">{
  "success": false,
  "error": "Error message"
}</div>
            
            <div class="note">
                <strong>Note:</strong> All endpoints support CORS and include proper OPTIONS preflight handling.
            </div>
        </div>
        
        <!-- Favorites Endpoint -->
        <div class="endpoint">
            <h2>Favorites</h2>
            <p>Manage user favorite movies.</p>
            
            <h3><span class="method get">GET</span> List Favorites</h3>
            <div class="endpoint-url">/api/favorites.php?user_id={id}</div>
            <p>Retrieve all favorite movies for a user.</p>
            
            <h4>Query Parameters</h4>
            <table class="params-table">
                <thead>
                    <tr>
                        <th>Parameter</th>
                        <th>Type</th>
                        <th>Required</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>user_id</code></td>
                        <td>integer</td>
                        <td>Yes</td>
                        <td>User ID (positive integer)</td>
                    </tr>
                </tbody>
            </table>
            
            <h4>Response Example</h4>
            <div class="code-block">{
  "success": true,
  "data": {
    "favorites": [
      {
        "id": 1,
        "title": "The Shawshank Redemption",
        "category": "Drama",
        "score": 9.3,
        "year": 1994,
        "favorited_at": "2026-01-20 10:30:00"
      }
    ],
    "count": 1
  }
}</div>
            
            <h3><span class="method post">POST</span> Add to Favorites</h3>
            <div class="endpoint-url">/api/favorites.php</div>
            <p>Add a movie to user's favorites.</p>
            
            <h4>Request Body</h4>
            <div class="code-block">{
  "user_id": 1,
  "movie_id": 42
}</div>
            
            <h4>Response Example</h4>
            <div class="code-block">{
  "success": true,
  "data": {
    "message": "Movie added to favorites"
  }
}</div>
            
            <h3><span class="method delete">DELETE</span> Remove from Favorites</h3>
            <div class="endpoint-url">/api/favorites.php</div>
            <p>Remove a movie from user's favorites.</p>
            
            <h4>Request Body</h4>
            <div class="code-block">{
  "user_id": 1,
  "movie_id": 42
}</div>
            
            <h4>HTTP Status Codes</h4>
            <ul class="response-codes">
                <li><code>200</code> - Success</li>
                <li><code>201</code> - Resource created</li>
                <li><code>400</code> - Bad request (invalid parameters)</li>
                <li><code>500</code> - Server error</li>
            </ul>
        </div>
        
        <!-- Watch Later Endpoint -->
        <div class="endpoint">
            <h2>Watch Later</h2>
            <p>Manage user watch later list.</p>
            
            <h3><span class="method get">GET</span> List Watch Later</h3>
            <div class="endpoint-url">/api/watch-later.php?user_id={id}[&include_watched=1]</div>
            <p>Retrieve watch later list for a user.</p>
            
            <h4>Query Parameters</h4>
            <table class="params-table">
                <thead>
                    <tr>
                        <th>Parameter</th>
                        <th>Type</th>
                        <th>Required</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>user_id</code></td>
                        <td>integer</td>
                        <td>Yes</td>
                        <td>User ID (positive integer)</td>
                    </tr>
                    <tr>
                        <td><code>include_watched</code></td>
                        <td>boolean</td>
                        <td>No</td>
                        <td>Include watched movies (default: false)</td>
                    </tr>
                </tbody>
            </table>
            
            <h4>Response Example</h4>
            <div class="code-block">{
  "success": true,
  "data": {
    "movies": [
      {
        "id": 1,
        "title": "Inception",
        "added_at": "2026-01-15 12:00:00",
        "watched": false,
        "watched_at": null
      }
    ],
    "count": 1,
    "unwatched_count": 1
  }
}</div>
            
            <h3><span class="method post">POST</span> Add to Watch Later</h3>
            <div class="endpoint-url">/api/watch-later.php</div>
            <p>Add a movie to watch later list.</p>
            
            <h4>Request Body</h4>
            <div class="code-block">{
  "user_id": 1,
  "movie_id": 42
}</div>
            
            <h3><span class="method patch">PATCH</span> Mark as Watched</h3>
            <div class="endpoint-url">/api/watch-later.php</div>
            <p>Mark a movie as watched.</p>
            
            <h4>Request Body</h4>
            <div class="code-block">{
  "user_id": 1,
  "movie_id": 42
}</div>
            
            <h3><span class="method delete">DELETE</span> Remove from Watch Later</h3>
            <div class="endpoint-url">/api/watch-later.php</div>
            <p>Remove a movie from watch later list.</p>
            
            <h4>Request Body</h4>
            <div class="code-block">{
  "user_id": 1,
  "movie_id": 42
}</div>
            
            <h4>HTTP Status Codes</h4>
            <ul class="response-codes">
                <li><code>200</code> - Success</li>
                <li><code>201</code> - Resource created</li>
                <li><code>400</code> - Bad request (invalid parameters)</li>
                <li><code>500</code> - Server error</li>
            </ul>
        </div>
        
        <!-- Ratings Endpoint -->
        <div class="endpoint">
            <h2>Ratings</h2>
            <p>Manage movie ratings and reviews.</p>
            
            <h3><span class="method get">GET</span> Get User Rating</h3>
            <div class="endpoint-url">/api/ratings.php?user_id={id}&movie_id={id}</div>
            <p>Get a specific user's rating for a movie.</p>
            
            <h4>Query Parameters</h4>
            <table class="params-table">
                <thead>
                    <tr>
                        <th>Parameter</th>
                        <th>Type</th>
                        <th>Required</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>user_id</code></td>
                        <td>integer</td>
                        <td>Yes</td>
                        <td>User ID (positive integer)</td>
                    </tr>
                    <tr>
                        <td><code>movie_id</code></td>
                        <td>integer</td>
                        <td>Yes</td>
                        <td>Movie ID (positive integer)</td>
                    </tr>
                </tbody>
            </table>
            
            <h4>Response Example</h4>
            <div class="code-block">{
  "success": true,
  "data": {
    "rating": {
      "id": 1,
      "user_id": 1,
      "movie_id": 42,
      "rating": 8.5,
      "review": "Great movie!",
      "created_at": "2026-01-20 10:00:00",
      "updated_at": "2026-01-20 10:00:00"
    }
  }
}</div>
            
            <h3><span class="method get">GET</span> Get Movie Statistics</h3>
            <div class="endpoint-url">/api/ratings.php?movie_id={id}</div>
            <p>Get rating statistics for a movie.</p>
            
            <h4>Response Example</h4>
            <div class="code-block">{
  "success": true,
  "data": {
    "average_rating": 8.5,
    "rating_count": 10,
    "ratings": [
      {
        "user_id": 1,
        "rating": 9.0,
        "review": "Excellent!",
        "created_at": "2026-01-20 10:00:00"
      }
    ]
  }
}</div>
            
            <h3><span class="method post">POST</span> Add Rating</h3>
            <div class="endpoint-url">/api/ratings.php</div>
            <p>Add a new rating for a movie.</p>
            
            <h4>Request Body</h4>
            <div class="code-block">{
  "user_id": 1,
  "movie_id": 42,
  "rating": 8.5,
  "review": "Great movie!" // Optional
}</div>
            
            <div class="note">
                <strong>Note:</strong> Rating must be between 1 and 10. If the user has already rated this movie, you'll receive a 409 Conflict error.
            </div>
            
            <h3><span class="method put">PUT</span> Update Rating</h3>
            <div class="endpoint-url">/api/ratings.php</div>
            <p>Update an existing rating.</p>
            
            <h4>Request Body</h4>
            <div class="code-block">{
  "user_id": 1,
  "movie_id": 42,
  "rating": 9.0,
  "review": "Even better on rewatch!" // Optional
}</div>
            
            <h3><span class="method delete">DELETE</span> Delete Rating</h3>
            <div class="endpoint-url">/api/ratings.php</div>
            <p>Delete a rating.</p>
            
            <h4>Request Body</h4>
            <div class="code-block">{
  "user_id": 1,
  "movie_id": 42
}</div>
            
            <h4>HTTP Status Codes</h4>
            <ul class="response-codes">
                <li><code>200</code> - Success</li>
                <li><code>201</code> - Resource created</li>
                <li><code>400</code> - Bad request (invalid parameters)</li>
                <li><code>404</code> - Rating not found</li>
                <li><code>409</code> - Conflict (rating already exists)</li>
                <li><code>500</code> - Server error</li>
                <li><code>501</code> - Not implemented</li>
            </ul>
        </div>
        
        <div class="intro">
            <h2>Error Handling</h2>
            <p>All endpoints use consistent error handling:</p>
            
            <h3>Validation Errors (400 Bad Request)</h3>
            <div class="code-block">{
  "success": false,
  "error": "user_id must be a positive integer"
}</div>
            
            <h3>Not Found (404)</h3>
            <div class="code-block">{
  "success": false,
  "error": "Rating not found"
}</div>
            
            <h3>Server Errors (500)</h3>
            <div class="code-block">{
  "success": false,
  "error": "An unexpected error occurred"
}</div>
            
            <div class="note">
                <strong>Security:</strong> Detailed error messages are logged server-side but generic messages are returned to clients to prevent information disclosure.
            </div>
        </div>
        
        <div class="intro">
            <h2>CORS Support</h2>
            <p>All endpoints support Cross-Origin Resource Sharing (CORS) with the following headers:</p>
            <ul>
                <li><code>Access-Control-Allow-Origin: *</code></li>
                <li><code>Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS</code></li>
                <li><code>Access-Control-Allow-Headers: Content-Type, Authorization</code></li>
                <li><code>Access-Control-Max-Age: 3600</code></li>
            </ul>
            <p>OPTIONS requests are handled for CORS preflight checks and return a 204 No Content response.</p>
        </div>
        
        <footer>
            <p>&copy; 2026 Movie Suggestor API | Phase 2 Implementation</p>
            <p>Built with PHP, MySQL, and RESTful best practices</p>
        </footer>
    </div>
</body>
</html>
