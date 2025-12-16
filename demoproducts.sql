-- First, ensure categories exist
INSERT INTO categories (name, description) VALUES
('Dolls', 'Beautiful and collectible dolls for all ages'),
('Action Figures', 'Action-packed figures from popular franchises'),
('Building Blocks', 'Educational and creative building sets'),
('Vehicles', 'Toy cars, trucks, and transportation vehicles'),
('Soft Toys', 'Cuddly and huggable stuffed animals'),
('Board Games', 'Fun games for family entertainment'),
('Puzzles', 'Challenging and engaging puzzle sets'),
('Educational Toys', 'STEM and learning-focused toys');

-- Insert demo products for Dolls category
INSERT INTO products (name, description, category_id, price, stock_quantity, discount_percent, is_featured) VALUES
('Princess Barbie Doll', 'Classic pink princess dress Barbie doll with long blonde hair and accessories', 1, 799, 50, 10, 1),
('Baby Born Interactive Doll', 'Interactive doll that eats, drinks, and cries realistically', 1, 1299, 30, 5, 1),
('American Girl Doll', 'Premium customizable American Girl doll with clothing and accessories', 1, 2499, 20, 0, 0),
('Elsa Frozen Doll', 'Disney Elsa doll with light-up gown and magical accessories', 1, 999, 40, 15, 1),
('LOL Surprise Doll', 'Mystery surprise doll with hidden layers and accessories', 1, 599, 60, 20, 1);

-- Insert demo products for Action Figures category
INSERT INTO products (name, description, category_id, price, stock_quantity, discount_percent, is_featured) VALUES
('Spider-Man Marvel Figure', 'Highly detailed Spider-Man action figure with interchangeable hands', 2, 549, 45, 10, 1),
('Iron Man Avengers Figure', 'Premium Iron Man figure with LED light effects and armor details', 2, 899, 25, 5, 0),
('Batman Dark Knight Figure', 'Batman action figure with cape and gadgets', 2, 699, 35, 12, 1),
('Naruto Ninja Figure Set', 'Set of 4 Naruto characters with different poses and accessories', 2, 1199, 20, 10, 1),
('Dinosaur T-Rex Figure', 'Large realistic dinosaur figure with moveable joints', 2, 449, 55, 15, 0);

-- Insert demo products for Building Blocks category
INSERT INTO products (name, description, category_id, price, stock_quantity, discount_percent, is_featured) VALUES
('LEGO Classic Set 500pc', 'Colorful LEGO bricks for creative building', 3, 999, 70, 10, 1),
('LEGO Harry Potter Hogwarts Set', 'Detailed Hogwarts castle building set with minifigures', 3, 3999, 15, 0, 1),
('DUPLO Farm Building Set', 'Large blocks for toddlers with farm theme', 3, 1499, 40, 5, 0),
('Magnetic Building Blocks 64pc', 'Magnetic tiles for 3D creative construction', 3, 1299, 35, 12, 1),
('Architecture Famous Buildings', 'Build famous landmarks like Eiffel Tower and Big Ben', 3, 2299, 25, 8, 0);

-- Insert demo products for Vehicles category
INSERT INTO products (name, description, category_id, price, stock_quantity, discount_percent, is_featured) VALUES
('Remote Control Sports Car', 'High-speed RC car with rechargeable battery and remote control', 4, 1599, 30, 10, 1),
('Die-cast Metal Car Set 12pc', 'Set of 12 detailed metal toy cars from different brands', 4, 799, 50, 15, 1),
('Hot Wheels Track Set', 'Complete track set with loop and multiple Hot Wheels cars', 4, 1999, 20, 5, 1),
('Toy Train Set with Track', 'Electric toy train with track, lights, and sound effects', 4, 2499, 15, 0, 0),
('Monster Truck Toy', 'Large monster truck with big wheels and realistic details', 4, 899, 40, 12, 0);

-- Insert demo products for Soft Toys category
INSERT INTO products (name, description, category_id, price, stock_quantity, discount_percent, is_featured) VALUES
('Teddy Bear Brown', 'Classic soft brown teddy bear, perfect for hugging', 5, 499, 80, 20, 1),
('Unicorn Plush Toy', 'Colorful unicorn with rainbow mane and soft plush material', 5, 599, 60, 10, 1),
('Panda Stuffed Animal', 'Cute and cuddly black and white panda plushie', 5, 449, 70, 15, 1),
('Lion King Plush Set', 'Set of 4 Lion King characters: Simba, Mufasa, Pumba, Timon', 5, 1299, 25, 5, 0),
('Hello Kitty Plush Toy', 'Adorable Hello Kitty with bow and official character details', 5, 399, 90, 25, 1);

-- Insert demo products for Board Games category
INSERT INTO products (name, description, category_id, price, stock_quantity, discount_percent, is_featured) VALUES
('Monopoly Board Game', 'Classic property trading board game for 2-8 players', 6, 799, 40, 10, 1),
('Ludo Game Board', 'Traditional colorful Ludo board game with tokens and dice', 6, 299, 100, 20, 1),
('Chess Board Set', 'Wooden chess set with pieces and instruction manual', 6, 1099, 30, 5, 0),
('Snake and Ladder Game', 'Nostalgic snake and ladder board game for family fun', 6, 249, 120, 25, 1),
('Uno Card Game', 'Fast-paced card game for 2-10 players of all ages', 6, 349, 80, 15, 0);

-- Insert demo products for Puzzles category
INSERT INTO products (name, description, category_id, price, stock_quantity, discount_percent, is_featured) VALUES
('Jigsaw Puzzle 1000pc', '1000 piece jigsaw puzzle with beautiful landscape image', 7, 599, 50, 10, 1),
('3D Puzzle Building Set', '3D puzzle of famous buildings with 500+ pieces', 7, 899, 25, 0, 1),
('Rubik\'s Cube', 'Original 3x3 Rubik\'s Cube puzzle game', 7, 399, 60, 15, 1),
('Wooden Puzzle Animals', 'Wooden animal-shaped puzzles for toddlers (6 puzzles)', 7, 449, 45, 20, 0),
('Tangram Puzzle Set', 'Classic Chinese tangram puzzle with multiple shapes', 7, 299, 70, 12, 1);

-- Insert demo products for Educational Toys category
INSERT INTO products (name, description, category_id, price, stock_quantity, discount_percent, is_featured) VALUES
('Science Experiment Kit', 'STEM science kit with 50+ experiments and materials', 8, 1899, 20, 5, 1),
('Microscope Set', 'Professional kid\'s microscope with slides and specimens', 8, 1499, 25, 0, 1),
('Coding Robot', 'Programmable robot for learning basic coding concepts', 8, 2299, 15, 10, 1),
('Geography Globe 3D', 'Interactive 3D globe showing countries and capitals', 8, 799, 35, 12, 0),
('Periodic Table Poster', 'Educational poster with interactive learning elements', 8, 349, 50, 20, 1);
