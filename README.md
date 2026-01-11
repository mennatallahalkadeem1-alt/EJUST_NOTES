# 📘 E-JUST Notes

A comprehensive web application for students at **E-JUST** (Egypt-Japan University of Science and Technology) to upload, share, browse, and rate academic notes, organized by program, level, and semester.

---

## ✨ Features

- 🔐 **User Authentication** – Secure login/signup with E-JUST email validation
- 📚 **Course-Based Note Browsing** – Filter notes by program, level, and semester
- ⭐ **Rating System** – Rate notes with 1–5 stars
- 📤 **File Upload** – Upload PDF, JPG, PNG files (max 10MB)
- 🗂️ **Personal Profile** – View and manage your uploaded notes
- 🧠 **Smart Filtering** – Automatically filters courses based on your program and level
- 📱 **Responsive Design** – Works on desktop, tablet, and mobile
- 🔍 **Quick Upload** – One-click upload from course folder view
- 🗑️ **Note Management** – Delete your own notes with confirmation

---

## 🛠️ Tech Stack

- **Frontend**: HTML, CSS (Flexbox, Grid), JavaScript
- **Backend**: PHP (PDO for database operations)
- **Database**: MySQL
- **File Storage**: Local `uploads/` directory
- **Avatar Generation**: DiceBear API

---

## 📁 Project Structure

```
ejust-notes/
├── index.php              # Login & registration page
├── home.php              # Main dashboard with notes browsing
├── upload.php            # Note upload form
├── profile.php           # User profile and note management
├── logout.php            # Session logout
├── db.php                # Database connection & table creation
├── uploads/              # Uploaded files directory (auto-created)
├── home.css              # Styles for home page
├── home.js               # JavaScript for home page interactivity
├── profile.css           # Profile page styles
├── upload.css            # Upload page styles
└── README.md             # This file
```

---

## 🗄️ Database Schema

### Users Table
- `id` (INT, PRIMARY KEY)
- `fullName` (VARCHAR)
- `email` (VARCHAR, UNIQUE)
- `password` (VARCHAR)
- `program` (VARCHAR) – CSC, AID, CNC, BIF
- `level` (INT) – 1–4
- `enrollmentYear` (INT)
- `profilePicture` (TEXT)

### Notes Table
- `id` (INT, PRIMARY KEY)
- `title` (VARCHAR)
- `course_id` (INT, FOREIGN KEY to courses.id)
- `description` (TEXT)
- `fileName` (VARCHAR)
- `filePath` (VARCHAR)
- `uploaderId` (INT, FOREIGN KEY to users.id)
- `uploaderName` (VARCHAR)
- `uploadDate` (DATE)

### Ratings Table
- `id` (INT, PRIMARY KEY)
- `note_id` (INT, FOREIGN KEY to notes.id)
- `user_id` (INT, FOREIGN KEY to users.id)
- `rating` (INT) – 1–5

### Courses Table (to be created)
- `id` (INT, PRIMARY KEY)
- `code` (VARCHAR) – Course code (e.g., "CSE101")
- `name` (VARCHAR) – Course name
- `level` (INT) – 1–4
- `semester` (VARCHAR) – "Fall", "Spring"
- `programs` (TEXT) – Comma-separated program IDs (e.g., "CSC,AID")

---

## 🚀 Setup Instructions

### 1. Clone Repository
```bash
git clone https://github.com/yourusername/ejust-notes.git
cd ejust-notes
```

### 2. Database Setup
Import the SQL schema (create the following tables):

```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullName VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    program VARCHAR(10) NOT NULL,
    level INT NOT NULL,
    enrollmentYear INT NOT NULL,
    profilePicture TEXT
);

CREATE TABLE notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    course_id INT NOT NULL,
    description TEXT,
    fileName VARCHAR(255) NOT NULL,
    filePath VARCHAR(255) NOT NULL,
    uploaderId INT NOT NULL,
    uploaderName VARCHAR(100) NOT NULL,
    uploadDate DATE DEFAULT (CURRENT_DATE),
    FOREIGN KEY (uploaderId) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id)
);

CREATE TABLE ratings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    note_id INT NOT NULL,
    user_id INT NOT NULL,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    FOREIGN KEY (note_id) REFERENCES notes(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_rating (note_id, user_id)
);

CREATE TABLE courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL,
    name VARCHAR(200) NOT NULL,
    level INT NOT NULL,
    semester VARCHAR(10) NOT NULL,
    programs TEXT NOT NULL
);
```

### 3. Configure Database
Edit `db.php` with your MySQL credentials:

```php
$host = 'localhost';
$user = 'your_username';
$pass = 'your_password';
$dbname = 'ejust_notes';
```

### 4. Create Uploads Directory
```bash
mkdir uploads
chmod 777 uploads  # Or appropriate permissions for your server
```

### 5. Add Courses Data
Insert your university courses into the `courses` table. Example:

```sql
INSERT INTO courses (code, name, level, semester, programs) VALUES
('CSE101', 'Introduction to Programming', 1, 'Fall', 'CSC,AID'),
('MAT101', 'Calculus I', 1, 'Fall', 'CSC,AID,CNC,BIF'),
-- Add more courses as needed
```

### 6. Run on Local Server
```bash
php -S localhost:8000
```
Then visit `http://localhost:8000`

---

## 🧪 Testing Accounts

Use the following format for emails:
- `name.32024XXXX@ejust.edu.eg` (replace XXXX with numbers)
- Example: `mohamed.320240001@ejust.edu.eg`

Password requirements:
- Minimum 6 characters
- Must contain letters and numbers

---

## 🎨 UI Components

- **Home Page**: Course grid, filters, rating system
- **Upload Page**: Form with course dropdown and file upload
- **Profile Page**: User stats and note management
- **Note Cards**: Display with preview, download button, and rating stars

---

## 🔒 Security Features

- Password hashing with `password_hash()`
- Prepared statements to prevent SQL injection
- Session-based authentication
- File type validation (PDF, JPG, PNG only)
- File size limit (10MB)
- E-JUST email validation regex

---

## 📊 Future Enhancevements

- [ ] Search functionality
- [ ] Advanced filters (by date, rating)
- [ ] Admin dashboard
- [ ] Email notifications
- [ ] Comments on notes
- [ ] File preview for all file types
- [ ] Dark mode

---

## 📝 License

This project is for educational purposes. Developed for E-JUST students.

---

## 👨‍💻 Author

[Your Name]  
E-JUST University  
CSIT Department

---

## 🤝 Contributing

Pull requests are welcome! For major changes, please open an issue first to discuss what you'd like to change.

---

**Note**: This project requires a running MySQL server and PHP 7.4+ with PDO extension enabled.