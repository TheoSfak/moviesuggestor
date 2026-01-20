# Phase 2 Architecture Diagram

## System Architecture Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                        USER INTERFACE LAYER                      │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐         │
│  │  index.php   │  │favorites.php │  │watch-later   │         │
│  │  (Browse)    │  │ (Favorites)  │  │   .php       │         │
│  └──────────────┘  └──────────────┘  └──────────────┘         │
│                                                                  │
│  ┌──────────────┐  ┌──────────────┐                            │
│  │  movie.php   │  │  admin.php   │                            │
│  │  (Details)   │  │  (Admin)     │                            │
│  └──────────────┘  └──────────────┘                            │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                     APPLICATION LAYER (PHP)                      │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  Core Classes:                                                   │
│  ┌────────────────────────────────────────────────────────┐    │
│  │ MovieRepository                                         │    │
│  │ - findByAdvancedFilters()                              │    │
│  │ - searchByText()                                       │    │
│  │ - findRelatedMovies()                                  │    │
│  │ - CRUD operations                                      │    │
│  └────────────────────────────────────────────────────────┘    │
│                                                                  │
│  ┌────────────────┐  ┌────────────────┐  ┌────────────────┐   │
│  │ Favorites      │  │ WatchLater     │  │ Rating         │   │
│  │ Repository     │  │ Repository     │  │ Repository     │   │
│  │                │  │                │  │                │   │
│  │ - add()        │  │ - add()        │  │ - addRating()  │   │
│  │ - remove()     │  │ - markWatched()│  │ - getAverage() │   │
│  │ - getFavorites │  │ - getList()    │  │ - update()     │   │
│  └────────────────┘  └────────────────┘  └────────────────┘   │
│                                                                  │
│  ┌────────────────┐  ┌────────────────┐  ┌────────────────┐   │
│  │ FilterBuilder  │  │ Paginator      │  │ MovieValidator │   │
│  │                │  │                │  │                │   │
│  │ - buildQuery() │  │ - getOffset()  │  │ - validate()   │   │
│  │ - getParams()  │  │ - getLimit()   │  │ - sanitize()   │   │
│  └────────────────┘  └────────────────┘  └────────────────┘   │
│                                                                  │
│  ┌────────────────┐  ┌────────────────┐                        │
│  │ SessionManager │  │ Database       │                        │
│  │                │  │                │                        │
│  │ - getSession() │  │ - connect()    │                        │
│  │ - validate()   │  │ - transaction()│                        │
│  └────────────────┘  └────────────────┘                        │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                       DATABASE LAYER (MySQL)                     │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ movies (Enhanced)                                        │   │
│  │ ─────────────────────────────────────────────────────   │   │
│  │ • id, title, category, score                            │   │
│  │ • trailer_url, description, created_at                  │   │
│  │ + release_year, director, actors                        │   │
│  │ + runtime_minutes, poster_url, backdrop_url             │   │
│  │ + imdb_rating, user_rating, votes_count                 │   │
│  │ + view_count, updated_at                                │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                  │
│  ┌───────────────┐  ┌───────────────┐  ┌──────────────────┐   │
│  │ favorites     │  │ watch_later   │  │ user_ratings     │   │
│  │ ─────────────│  │ ───────────── │  │ ──────────────── │   │
│  │ • id          │  │ • id          │  │ • id             │   │
│  │ • session_id  │  │ • session_id  │  │ • session_id     │   │
│  │ • movie_id    │  │ • movie_id    │  │ • movie_id       │   │
│  │ • created_at  │  │ • watched     │  │ • rating         │   │
│  │               │  │ • created_at  │  │ • created_at     │   │
│  │               │  │ • watched_at  │  │ • updated_at     │   │
│  └───────────────┘  └───────────────┘  └──────────────────┘   │
│                                                                  │
│  ┌───────────────┐  ┌───────────────────────────────────────┐  │
│  │ genres        │  │ movie_genres                          │  │
│  │ ───────────── │  │ ────────────────────────────────────  │  │
│  │ • id          │  │ • movie_id (FK → movies.id)          │  │
│  │ • name        │  │ • genre_id (FK → genres.id)          │  │
│  │ • slug        │  │ • created_at                          │  │
│  │ • description │  │                                       │  │
│  └───────────────┘  └───────────────────────────────────────┘  │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

## Data Flow Diagrams

### 1. Browse Movies Flow

```
User → index.php
   │
   ├─ Select Filters (Category, Score, Year, Search)
   │     │
   │     ▼
   │  FilterBuilder.buildQuery()
   │     │
   │     ▼
   │  MovieRepository.findByAdvancedFilters()
   │     │
   │     ▼
   │  Database Query (movies table + filters)
   │     │
   │     ▼
   │  Paginator.paginate(results)
   │     │
   │     ▼
   └─ Display Movie Cards
```

### 2. Add to Favorites Flow

```
User → Click "Add to Favorites" Button
   │
   ▼
SessionManager.getSessionId()
   │
   ▼
FavoritesRepository.addFavorite(sessionId, movieId)
   │
   ├─ Check if already favorited
   │     │
   │     ▼ No
   │  INSERT INTO favorites
   │     │
   │     ▼
   └─ Return success
   │
   ▼
Update UI (heart icon filled)
```

### 3. Rate Movie Flow

```
User → Submit Rating (0.0 - 10.0)
   │
   ▼
MovieValidator.validateScore(rating)
   │
   ▼ Valid
SessionManager.getSessionId()
   │
   ▼
RatingRepository.addRating(sessionId, movieId, rating)
   │
   ├─ INSERT INTO user_ratings
   │     │
   │     ▼
   │  Database Trigger Fires
   │     │
   │     ├─ Calculate AVG(rating)
   │     ├─ Update movies.user_rating
   │     └─ Update movies.votes_count
   │
   ▼
Display updated average rating
```

### 4. Admin Add Movie Flow

```
Admin → Fill Movie Form
   │
   ▼
MovieValidator.validateMovieData()
   │
   ├─ Validate title (required, max 255)
   ├─ Validate category (valid category)
   ├─ Validate score (0.0-10.0)
   ├─ Validate year (1900-2026)
   ├─ Validate URLs (valid format)
   │
   ▼ All Valid
MovieRepository.createMovie(data)
   │
   ├─ INSERT INTO movies
   │     │
   │     ▼
   │  Get last insert ID
   │     │
   │     ▼
   │  INSERT INTO movie_genres (link genres)
   │
   ▼
Redirect to movie list
```

## Component Dependencies

```
┌─────────────────────────────────────────────────┐
│                  UI Pages                        │
│  (index, favorites, watch-later, movie, admin)  │
└────────────────┬────────────────────────────────┘
                 │
                 ├─ Depends on ─────────┐
                 │                      │
                 ▼                      ▼
┌────────────────────────────┐  ┌──────────────────┐
│    Repositories            │  │  Utility Classes │
│  (Movie, Favorites, etc.)  │  │  (Filter, Pager) │
└────────────┬───────────────┘  └────────┬─────────┘
             │                           │
             ├─────── Depends on ────────┤
             │                           │
             ▼                           ▼
┌────────────────────────────────────────────────┐
│             Database Class                      │
└────────────────┬───────────────────────────────┘
                 │
                 ▼
┌────────────────────────────────────────────────┐
│             MySQL Database                      │
└────────────────────────────────────────────────┘
```

## Database Relationships (ERD)

```
                   ┌─────────────┐
                   │   movies    │
                   │─────────────│
                   │ PK id       │
                   │    title    │
                   │    category │
                   │    score    │
                   │ +  year     │
                   │ +  director │
                   │ +  poster   │
                   │ +  rating   │
                   └──────┬──────┘
                          │
        ┌─────────────────┼─────────────────┬──────────────┐
        │                 │                 │              │
        │ 1               │ 1               │ 1            │ 1
        │                 │                 │              │
        │ N               │ N               │ N            │ N
┌───────▼──────┐  ┌───────▼──────┐  ┌──────▼──────┐  ┌───▼──────────┐
│  favorites   │  │ watch_later  │  │user_ratings │  │movie_genres  │
│──────────────│  │──────────────│  │─────────────│  │──────────────│
│ PK id        │  │ PK id        │  │ PK id       │  │ PK movie_id  │
│    sessionId │  │    sessionId │  │    sessionId│  │ PK genre_id  │
│ FK movieId   │  │ FK movieId   │  │ FK movieId  │  │              │
│    createdAt │  │    watched   │  │    rating   │  │              │
└──────────────┘  │    watchedAt │  │    createdAt│  └──────────────┘
                  └──────────────┘  └─────────────┘          │
                                                              │ N
                                                              │
                                                              │ 1
                                                    ┌─────────▼──────┐
                                                    │    genres      │
                                                    │────────────────│
                                                    │ PK id          │
                                                    │    name        │
                                                    │    slug        │
                                                    └────────────────┘
```

## Session Management Architecture

```
┌─────────────┐
│   Browser   │
│             │
│ Cookie:     │
│ session_id= │
│ abc123xyz   │
└──────┬──────┘
       │
       │ HTTP Request with Cookie
       │
       ▼
┌─────────────────────────────────┐
│   SessionManager                 │
│                                  │
│  1. Read session_id from cookie │
│  2. Validate format (64 chars)  │
│  3. If missing, generate new    │
│  4. Set secure cookie           │
│     - HttpOnly                  │
│     - SameSite=Strict           │
│     - 30 day expiry             │
│                                  │
└──────────────┬──────────────────┘
               │
               │ Use session_id for queries
               │
               ▼
┌─────────────────────────────────┐
│   Database Tables                │
│                                  │
│  - favorites.session_id         │
│  - watch_later.session_id       │
│  - user_ratings.session_id      │
│                                  │
└──────────────────────────────────┘
```

## Filter Processing Architecture

```
User Input:
┌──────────────────────────────────┐
│ Categories: [Action, Sci-Fi]     │
│ Score: 8.0 - 10.0                │
│ Year: 2000 - 2020                │
│ Search: "space"                  │
│ Sort: Score DESC                 │
│ Page: 2                          │
└──────────┬───────────────────────┘
           │
           ▼
┌──────────────────────────────────┐
│     FilterBuilder                │
│                                  │
│  buildQuery():                   │
│                                  │
│  SELECT * FROM movies            │
│  WHERE (category IN (:cat1,:cat2)│
│     OR category IS NULL)         │
│  AND score >= :minScore          │
│  AND score <= :maxScore          │
│  AND release_year >= :minYear    │
│  AND release_year <= :maxYear    │
│  AND (title LIKE :search         │
│      OR description LIKE :search)│
│  ORDER BY score DESC             │
│  LIMIT :limit OFFSET :offset     │
│                                  │
│  getParameters():                │
│  [                               │
│    'cat1' => 'Action',           │
│    'cat2' => 'Sci-Fi',           │
│    'minScore' => 8.0,            │
│    'maxScore' => 10.0,           │
│    'minYear' => 2000,            │
│    'maxYear' => 2020,            │
│    'search' => '%space%'         │
│  ]                               │
└──────────┬───────────────────────┘
           │
           ▼
┌──────────────────────────────────┐
│   MovieRepository                │
│                                  │
│  $stmt = $db->prepare($sql);    │
│  $stmt->execute($params);        │
│  return $stmt->fetchAll();       │
└──────────┬───────────────────────┘
           │
           ▼
┌──────────────────────────────────┐
│   Paginator                      │
│                                  │
│  Total: 156 movies               │
│  Per Page: 12                    │
│  Current: Page 2                 │
│                                  │
│  Offset: 12                      │
│  Limit: 12                       │
│  Total Pages: 13                 │
│  Has Next: true                  │
│  Has Prev: true                  │
└──────────┬───────────────────────┘
           │
           ▼
┌──────────────────────────────────┐
│   Display Results                │
│                                  │
│  Showing 13-24 of 156            │
│  [Movie Cards...]                │
│  « Previous | 1 2 3 ... 13 | Next »
└──────────────────────────────────┘
```

## Admin Panel Flow

```
┌──────────────┐
│   Admin      │
│   Login      │
└──────┬───────┘
       │
       ▼
┌─────────────────────────────────┐
│  Admin Dashboard                 │
│                                  │
│  ┌────────────────────────────┐ │
│  │ [Add New Movie]            │ │
│  └────────────────────────────┘ │
│                                  │
│  Movie List:                     │
│  ┌────────────────────────────┐ │
│  │ Movie 1  [Edit] [Delete]   │ │
│  │ Movie 2  [Edit] [Delete]   │ │
│  │ Movie 3  [Edit] [Delete]   │ │
│  └────────────────────────────┘ │
│                                  │
│  [Import CSV]                   │
└─────────────────────────────────┘
           │
           ├─ Add Movie ──────────┐
           │                      │
           │                      ▼
           │          ┌───────────────────────┐
           │          │ MovieValidator        │
           │          │  - Validate all fields│
           │          │  - Sanitize inputs    │
           │          └───────┬───────────────┘
           │                  │
           │                  ▼
           │          ┌───────────────────────┐
           │          │ MovieRepository       │
           │          │  - createMovie()      │
           │          │  - Insert to DB       │
           │          └───────────────────────┘
           │
           ├─ Edit Movie ──────────┐
           │                       │
           │                       ▼
           │          ┌────────────────────────┐
           │          │ Load Movie Data        │
           │          │ Pre-fill Form          │
           │          └────────┬───────────────┘
           │                   │
           │                   ▼
           │          ┌────────────────────────┐
           │          │ MovieValidator         │
           │          │ MovieRepository        │
           │          │  - updateMovie()       │
           │          └────────────────────────┘
           │
           └─ Delete Movie ──────────┐
                                     │
                                     ▼
                          ┌──────────────────────┐
                          │ Confirmation Dialog  │
                          │ "Are you sure?"      │
                          └──────┬───────────────┘
                                 │ Yes
                                 ▼
                          ┌──────────────────────┐
                          │ MovieRepository      │
                          │  - deleteMovie()     │
                          │  - CASCADE deletes:  │
                          │    * favorites       │
                          │    * watch_later     │
                          │    * user_ratings    │
                          │    * movie_genres    │
                          └──────────────────────┘
```

## Testing Architecture

```
┌──────────────────────────────────────────────────────┐
│                  Test Suite                           │
├──────────────────────────────────────────────────────┤
│                                                       │
│  Unit Tests (70+ tests)                              │
│  ┌────────────────────────────────────────────────┐ │
│  │ • FilterBuilderTest                            │ │
│  │ • FavoritesRepositoryTest                      │ │
│  │ • WatchLaterRepositoryTest                     │ │
│  │ • RatingRepositoryTest                         │ │
│  │ • PaginatorTest                                │ │
│  │ • MovieValidatorTest                           │ │
│  │ • SessionManagerTest                           │ │
│  │ • Enhanced MovieRepositoryTest                 │ │
│  └────────────────────────────────────────────────┘ │
│                                                       │
│  Integration Tests (10+ tests)                       │
│  ┌────────────────────────────────────────────────┐ │
│  │ • End-to-end user flows                        │ │
│  │ • Browse → Filter → View → Favorite           │ │
│  │ • Rate movie → See average update              │ │
│  │ • Admin CRUD operations                        │ │
│  └────────────────────────────────────────────────┘ │
│                                                       │
│  Security Tests (20+ tests)                          │
│  ┌────────────────────────────────────────────────┐ │
│  │ • SQL injection attempts                       │ │
│  │ • XSS prevention verification                  │ │
│  │ • CSRF token validation                        │ │
│  │ • Input validation edge cases                  │ │
│  │ • Session security                             │ │
│  └────────────────────────────────────────────────┘ │
│                                                       │
│  Target: 90%+ Code Coverage                          │
│                                                       │
└──────────────────────────────────────────────────────┘
```

## Technology Stack

```
┌─────────────────────────────────────────┐
│           Frontend                       │
│  • HTML5                                │
│  • CSS3 (Responsive, Flexbox, Grid)    │
│  • Vanilla JavaScript                   │
│  • No frameworks (Plain PHP)            │
└─────────────────────────────────────────┘
                    │
                    ▼
┌─────────────────────────────────────────┐
│           Backend                        │
│  • PHP 8.0+                             │
│  • PDO (Database abstraction)           │
│  • Object-Oriented Design               │
│  • Repository Pattern                   │
│  • Dependency Injection                 │
└─────────────────────────────────────────┘
                    │
                    ▼
┌─────────────────────────────────────────┐
│           Database                       │
│  • MySQL 8.0+                           │
│  • InnoDB Engine                        │
│  • UTF8MB4 Character Set               │
│  • Triggers for auto-calculations       │
│  • Foreign Keys for integrity           │
└─────────────────────────────────────────┘
                    │
                    ▼
┌─────────────────────────────────────────┐
│           Testing                        │
│  • PHPUnit 10                           │
│  • PHPStan (Static Analysis)           │
│  • PHP CS Fixer (Code Style)           │
└─────────────────────────────────────────┘
                    │
                    ▼
┌─────────────────────────────────────────┐
│           CI/CD                          │
│  • GitHub Actions                       │
│  • Automated Testing                    │
│  • Code Coverage Reports                │
│  • Judge Evaluation                     │
└─────────────────────────────────────────┘
```

---

*This architecture diagram provides a visual overview of Phase 2 implementation structure.*
