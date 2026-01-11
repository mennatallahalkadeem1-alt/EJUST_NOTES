# Be Cosmo – Social Media Platform

## 🌐 Be Cosmo – Social Media Platform

**Be Cosmo** is a minimalist and responsive social media web application that allows users to create accounts, manage profiles, and interact with posts in real time. The platform offers a seamless user experience through simple authentication, intuitive navigation, and engaging social features such as likes, comments, and following.

---

## ✨ Features

### 🔐 User Authentication
- User registration, login, and logout
- Secure password encryption
- Email verification for account validation

### 📝 Post Management
- Create, edit, and delete text-based posts
- Support for media content: images, videos, and links

### 📰 Feed / Timeline
- Displays posts from all users or followed users
- Posts sorted by recency or popularity

### ❤️ Likes & Comments
- Like and unlike posts
- Add, view, and manage comments

### 🔍 Search Functionality
- Search users or posts by keywords

### 👥 Follow System
- Follow or unfollow other users
- View followers and following lists

### 🔔 Notifications
- Real-time alerts for likes, comments, and follows

### 📱 Responsive Design
- Optimized for both desktop and mobile devices

### ⚙️ Account Settings
- Change password and email address
- Update username
- Toggle between light and dark mode

---

## 🛠️ Requirements

To run this project locally, you will need:

- Any operating system (Windows, macOS, or Linux)
- Python 3.8 or higher
- Flutter SDK and [Flet](https://flet.dev) (Python UI framework)
- Any code editor (e.g., VS Code, PyCharm, Android Studio)
- A Firebase project configured with:
  - Firestore
  - Firebase Authentication
- Basic knowledge of Python, Flutter/Flet, and Firebase

---

## 🧱 Tech Stack

### 👨‍💻 Frontend
- **Flutter Flet** – for cross-platform UI development using Python
- **Dart (under the hood)** – powers the Flet framework
- **Flet (Python package)** – reactive front-end framework

### 🧠 Backend
- **Python** – main server-side language
- **requests** – used for HTTP requests to access Firebase services

### ☁️ Cloud Services (Firebase)
- **Firebase Authentication** – handles user auth
- **Cloud Firestore** – real-time database

---

## 🔥 Database Structure (Firebase Firestore)

The app uses **Cloud Firestore** to manage user and post data. Below is the structure:

### 📁 Collections

#### 1. `users`
Each document represents a single user profile.

**Fields:**
- `createdAt`: Timestamp of account creation
- `displayName`: User's display name
- `email`: User's email
- `followers`: Map of user IDs who follow this user
- `following`: Map of user IDs this user follows
- `notifications`: User-specific notifications

**Subcollections:**
- `chats`: Each document represents a chat or conversation thread (e.g., `intro_chat`)

---

#### 2. `posts`
Each document represents a user-created post.

**Fields:**
- `text`: Post content
- `timePosted`: Timestamp of creation
- `username`: Creator of the post
- `comments`: Array of comment objects or strings
- `likes`: Array of user IDs who liked the post

---

## 📸 Screenshots

Below are screenshots showcasing the **Be Cosmo** app's key features and responsive design across devices. (Replace the placeholders with actual image links or paths after capturing the screenshots.)

- **Login Screen**: Displays the user authentication interface.
  
  <img src="screenshots/login.png" alt="Login Screen" width="70%">

- **User Profile**: Shows user details, followers, and following lists.  
  <img src="userprofile.png" alt="user profile Screen" width="70%">

- **Feed/Timeline**: Displays posts sorted by recency or popularity.
- 
  <img src="screenshots/feed.png" alt="feed Screen" width="70%">

- **Post Interaction**: Highlights likes and comments on a post.  
  <img src="screenshots/interactionWithPost.png" alt="feed2 Screen" width="70%">

- **Chat**: Shows chat screen  
  <img src="screenshots/chat.png" alt="Chat Screen" width="70%">

---

## Installation

Build Be_Cosmo from the source and install dependencies:

1. Clone the repository:
   ```bash
   git clone https://github.com/anoureen2006/Be_Cosmo
   ```

2. Navigate to the project directory:
   ```bash
   cd Be_Cosmo
   ```

3. Install the dependencies:
   ```bash
   # Add dependency installation command here (e.g., pip install -r requirements.txt)
   ```

---
