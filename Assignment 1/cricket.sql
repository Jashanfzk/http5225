-- Create countries table
CREATE TABLE countries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    continent VARCHAR(100)
);

-- Insert data into countries table
INSERT INTO countries (name, continent) VALUES
('India', 'Asia'),
('Australia', 'Oceania'),
('South Africa', 'Africa'),
('West Indies', 'North America'),
('Sri Lanka', 'Asia');

-- Create cricket_players table with foreign key to countries
CREATE TABLE cricket_players (
    id INT AUTO_INCREMENT PRIMARY KEY,
    player_name VARCHAR(100),
    country_id INT,
    matches INT,
    runs INT,
    average DECIMAL(5,2),
    debut_year YEAR,
    FOREIGN KEY (country_id) REFERENCES countries(id)
);

-- Insert data into cricket_players table
INSERT INTO cricket_players (player_name, country_id, matches, runs, average, debut_year) VALUES
('Sachin Tendulkar', 1, 463, 18426, 44.83, 1989),
('Ricky Ponting', 2, 375, 13704, 42.03, 1995),
('Virat Kohli', 1, 275, 12898, 57.38, 2008),
('Jacques Kallis', 3, 328, 11579, 44.36, 1996),
('MS Dhoni', 1, 350, 10773, 50.57, 2004),
('Brian Lara', 4, 299, 10405, 40.48, 1990),
('Kumar Sangakkara', 5, 404, 14234, 41.98, 2000),
('Rahul Dravid', 1, 344, 10889, 39.16, 1996),
('AB de Villiers', 3, 228, 9577, 53.50, 2005),
('Chris Gayle', 4, 301, 10480, 38.04, 1999);
