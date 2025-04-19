# EchoBridge - A Social Media Web Application

EchoBridge is a dynamic social media platform designed for connecting users through posts, likes, comments, and private messages. Built with robust backend and a clean, modern front-end, EchoBridge offers a seamless user experience for social networking.

## Features

- **User Authentication**: Secure login and registration system.
- **Post Creation**: Users can share their thoughts, decide visibility (`public`, `friends`, `private`), and upload media.
- **Likes and Comments**: Engage with posts through likes and comments.
- **Friendship Management**: Send, accept, and manage friend requests.
- **Private Messaging**: Communicate directly with other users.
- **Groups and Memberships**: Create or join groups with roles like `admin` or `member`.
- **Privacy Settings**: Control profile visibility, post visibility, and message permissions.
- **Reports and Moderation**: Report inappropriate posts and comments for moderation.

---

## Technologies Used

### Backend
- **PHP**: Core backend language for handling server-side logic.
- **MySQL**: Relational database for managing users, posts, likes, comments, and more.

### Frontend
- **HTML5 & CSS3**: Clean and responsive design.
- **JavaScript (jQuery)**: Dynamic updates with AJAX for real-time interactions.

---

## Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/your-username/mysocial.git
   ```

2. Navigate to the project directory:
   ```bash
   cd mysocial
   ```

3. Import the database:
   - Open `phpMyAdmin` or any MySQL client.
   - Import the SQL file (`social.sql`) to create the required tables and seed data.

4. Configure the database connection:
   - Open `db_connect.php`.
   - Fill in your database credentials:
     ```php
     $servername = "localhost";
     $username = "root"; // Your database username
     $password = ""; // Your database password
     $dbname = "social"; // Your database name
     ```

5. Start the server:
   - Use XAMPP, WAMP, or any PHP-enabled server to run the project.

6. Access the application:
   - Open your browser and go to `http://localhost/mysocial`.

---

## Folder Structure

```
mysocial/
├── db_connect.php      # Database connection script
├── index.php           # Dashboard and home page
├── profile.php         # User profile management
├── posts.php           # Post creation and feed
├── messages.php        # Private messaging system
├── groups.php          # Group management
├── settings.php        # Privacy and account settings
├── assets/             # Static files (CSS, JS, images)
│   ├── css/
│   ├── js/
│   └── images/
└── README.md           # Documentation
```

---

## How to Contribute

1. Fork the repository.
2. Create a new branch:
   ```bash
   git checkout -b feature-branch-name
   ```
3. Commit your changes:
   ```bash
   git commit -m "Add new feature"
   ```
4. Push to your fork:
   ```bash
   git push origin feature-branch-name
   ```
5. Submit a pull request.

---

## License

This project is licensed under the MIT License. See `LICENSE` for details.

---

## Contact

For any inquiries or contributions, please reach out via agabaolivier85@gmail.com.
