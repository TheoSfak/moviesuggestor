<?php

namespace MovieSuggestor;

class MovieRepository
{
    public function __construct(private Database $database)
    {
    }

    public function findByFilters(string $category = '', float $minScore = 0.0): array
    {
        // Validate and sanitize inputs
        $category = trim($category);
        $minScore = max(0.0, min(10.0, $minScore)); // Clamp to valid range
        
        $db = $this->database->connect();
        
        $sql = "SELECT id, title, category, score, trailer_url, description FROM movies WHERE 1=1";
        $params = [];

        if (!empty($category)) {
            $sql .= " AND category = :category";
            $params['category'] = $category;
        }

        if ($minScore > 0) {
            $sql .= " AND score >= :minScore";
            $params['minScore'] = $minScore;
        }

        $sql .= " ORDER BY score DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }

    public function getAllCategories(): array
    {
        $db = $this->database->connect();
        $stmt = $db->query("SELECT DISTINCT category FROM movies ORDER BY category");
        return array_column($stmt->fetchAll(), 'category');
    }
}
