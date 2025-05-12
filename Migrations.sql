DROP DATABASE IF EXISTS eklas;
CREATE DATABASE eklas;
USE eklas;

-- Skolotāji
CREATE TABLE teachers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    profile_image VARCHAR(255) DEFAULT NULL
);

-- Skolēni
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    profile_image VARCHAR(255) DEFAULT NULL
);

-- Priekšmeti ar skolotāja piesaisti
CREATE TABLE subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_name VARCHAR(100) NOT NULL,
    teacher_id INT NOT NULL,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
);

-- Atzīmes
CREATE TABLE grades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    subject_id INT NOT NULL,
    grade INT CHECK (grade >= 1 AND grade <= 10),
    date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
);

-- Skolotāji (2 gab.)
INSERT INTO teachers (first_name, last_name, email) VALUES 
('Skolotājs', 'Viens', 'skolotajs1@vtdt.edu.lv'),
('Skolotājs', 'Divi', 'skolotajs2@vtdt.edu.lv');

-- Priekšmeti
INSERT INTO subjects (subject_name, teacher_id) VALUES 
('Matemātika', 1),
('Latviešu valoda', 1),
('Vēsture', 2),
('Bioloģija', 2),
('Fizika', 1);

-- Skolēni (15 gab.)
INSERT INTO students (first_name, last_name, email) VALUES 
('Anna', 'Ozola', 'anna@vtdt.edu.lv'),
('Jānis', 'Kalniņš', 'janis@vtdt.edu.lv'),
('Laura', 'Liepa', 'laura@vtdt.edu.lv'),
('Roberts', 'Bērziņš', 'roberts@vtdt.edu.lv'),
('Marta', 'Vītola', 'marta@vtdt.edu.lv'),
('Edgars', 'Lapiņš', 'edgars@vtdt.edu.lv'),
('Liene', 'Grase', 'liene@vtdt.edu.lv'),
('Toms', 'Ziediņš', 'toms@vtdt.edu.lv'),
('Sanita', 'Riekstiņa', 'sanita@vtdt.edu.lv'),
('Kārlis', 'Vilciņš', 'karlis@vtdt.edu.lv'),
('Ilze', 'Dūja', 'ilze@vtdt.edu.lv'),
('Reinis', 'Balodis', 'reinis@vtdt.edu.lv'),
('Dace', 'Zvaigzne', 'dace@vtdt.edu.lv'),
('Oskars', 'Priedītis', 'oskars@vtdt.edu.lv'),
('Zane', 'Lazdiņa', 'zane@vtdt.edu.lv');

-- Atzīmes
INSERT INTO grades (student_id, subject_id, grade) VALUES
(1,1,8),(1,2,7),(1,3,9),(1,4,6),(1,5,10),
(2,1,6),(2,2,8),(2,3,7),(2,4,5),(2,5,9),
(3,1,9),(3,2,9),(3,3,8),(3,4,7),(3,5,9),
(4,1,5),(4,2,6),(4,3,7),(4,4,6),(4,5,8),
(5,1,10),(5,2,9),(5,3,9),(5,4,10),(5,5,10),
(6,1,7),(6,2,7),(6,3,6),(6,4,8),(6,5,9),
(7,1,9),(7,2,8),(7,3,9),(7,4,7),(7,5,8),
(8,1,6),(8,2,5),(8,3,7),(8,4,6),(8,5,7),
(9,1,10),(9,2,9),(9,3,8),(9,4,9),(9,5,10),
(10,1,7),(10,2,8),(10,3,6),(10,4,7),(10,5,8),
(11,1,6),(11,2,7),(11,3,6),(11,4,5),(11,5,6),
(12,1,9),(12,2,8),(12,3,7),(12,4,9),(12,5,8),
(13,1,5),(13,2,5),(13,3,6),(13,4,6),(13,5,7),
(14,1,8),(14,2,7),(14,3,7),(14,4,8),(14,5,9),
(15,1,10),(15,2,10),(15,3,9),(15,4,10),(15,5,10);
