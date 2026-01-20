-- Movie Suggestor Database Schema

CREATE TABLE IF NOT EXISTS movies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    category VARCHAR(100) NOT NULL,
    score DECIMAL(3,1) NOT NULL,
    trailer_url VARCHAR(500),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Sample data
INSERT INTO movies (title, category, score, trailer_url, description) VALUES
('The Shawshank Redemption', 'Drama', 9.3, 'https://www.youtube.com/watch?v=6hB3S9bIaco', 'Two imprisoned men bond over a number of years.'),
('The Godfather', 'Crime', 9.2, 'https://www.youtube.com/watch?v=sY1S34973zA', 'The aging patriarch of an organized crime dynasty transfers control.'),
('The Dark Knight', 'Action', 9.0, 'https://www.youtube.com/watch?v=EXeTwQWrcwY', 'Batman faces the Joker in Gotham City.'),
('Pulp Fiction', 'Crime', 8.9, 'https://www.youtube.com/watch?v=s7EdQ4FqbhY', 'Various interconnected stories of Los Angeles criminals.'),
('Forrest Gump', 'Drama', 8.8, 'https://www.youtube.com/watch?v=bLvqoHBptjg', 'The presidencies of Kennedy and Johnson unfold through the perspective of an Alabama man.'),
('Inception', 'Sci-Fi', 8.8, 'https://www.youtube.com/watch?v=YoHD9XEInc0', 'A thief who steals corporate secrets through dream-sharing technology.'),
('The Matrix', 'Sci-Fi', 8.7, 'https://www.youtube.com/watch?v=vKQi3bBA1y8', 'A computer hacker learns about the true nature of his reality.'),
('Goodfellas', 'Crime', 8.7, 'https://www.youtube.com/watch?v=2ilzidi_J8Q', 'The story of Henry Hill and his life in the mob.'),
('Interstellar', 'Sci-Fi', 8.6, 'https://www.youtube.com/watch?v=zSWdZVtXT7E', 'A team of explorers travel through a wormhole in space.'),
('The Lion King', 'Animation', 8.5, 'https://www.youtube.com/watch?v=lFzVJEksoDY', 'Lion cub and future king Simba searches for his identity.'),
('Toy Story', 'Animation', 8.3, 'https://www.youtube.com/watch?v=v-PjgYDrg70', 'A cowboy doll is profoundly threatened by a new spaceman figure.'),
('Die Hard', 'Action', 8.2, 'https://www.youtube.com/watch?v=jaJuwKCmJbY', 'An NYPD officer tries to save his wife and others taken hostage.'),
('Spirited Away', 'Animation', 8.6, 'https://www.youtube.com/watch?v=ByXuk9QqQkk', 'A young girl enters a world of spirits and witches.'),
('The Notebook', 'Romance', 7.8, 'https://www.youtube.com/watch?v=4M7LOvJTG_o', 'A poor yet passionate young man falls in love with a rich young woman.'),
('Titanic', 'Romance', 7.9, 'https://www.youtube.com/watch?v=2e-eXJ6HgkQ', 'A seventeen-year-old aristocrat falls in love with a kind artist.');
