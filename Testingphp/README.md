# BrickMMO Timesheets Management System

## Capstone Project Documentation

**Author:** Jashanpreet Singh Gill  
**Institution:** Humber College  
**Course:** Capstone  
**Date:** November 13, 2025

---

## Table of Contents

1. [Introduction](#introduction)
2. [Problem Statement](#problem-statement)
3. [Project Objectives](#project-objectives)
4. [System Architecture](#system-architecture)
5. [Technology Stack](#technology-stack)
6. [System Features](#system-features)
7. [Database Design](#database-design)
8. [Implementation Details](#implementation-details)
9. [Security Features](#security-features)
10. [User Interface Design](#user-interface-design)
11. [Testing](#testing)
12. [Deployment Guide](#deployment-guide)
13. [Future Improvements](#future-improvements)
14. [What I Learned](#what-i-learned)
15. [References](#references)

---

## Introduction

### Background

So I've been working with the BrickMMO project for a while now, and there's a real problem - we have multiple repos and contributors, but no good way to track who's working on what and for how long. People were using spreadsheets, sticky notes, or just trying to remember what they worked on. It's messy and we lose track of things.

I thought there has to be a better way, so for my capstone project, I decided to build a timesheet system that integrates with GitHub. Since we're already using GitHub for everything, why not use it for authentication too?

### What I Built

This is a web application that lets contributors log their hours, tracks what repos they worked on, and gives admins a way to see what everyone's been doing. Users can log in with their GitHub account (no separate passwords needed), log hours for different repos, and see their own history. Admins can import repos from GitHub and manage the system.

### Development Time

This took me about 2 months to build, working on it part-time around classes and other commitments. I had to learn a lot about OAuth, GitHub's API, and web security along the way.

---

## Problem Statement

### The Issues We Had

1. **Everyone was tracking time differently** - Some people used Google Sheets, others used notes apps, some didn't track at all. Data was getting lost.

2. **No centralized view** - If I wanted to see what everyone worked on across all our repos, I'd have to ask around or dig through different spreadsheets.

3. **Manual work** - Making reports meant manually collecting data from everyone, which took forever and had errors.

4. **Hard to see progress** - As a project lead, there was no easy way to see who was contributing what and when.

5. **Too many accounts** - We didn't want to make people create yet another account when they already have GitHub.

### My Solution

I built a single web app where:
- Users log in with GitHub (no new accounts!)
- Everyone logs hours in the same place
- The system pulls repo info from GitHub automatically
- Admins can see reports and manage repos
- It's all in one place and everyone uses the same system

---

## Project Objectives

### Main Goals

1. **GitHub OAuth Login**
   - Let users sign in with their GitHub account
   - Automatically create/update their profile
   - Keep sessions secure

2. **Time Logging**
   - Easy form to log hours worked
   - Select which repo you worked on
   - Pick a date and enter hours
   - Optional description of what you did

3. **User Dashboard**
   - See your total hours logged
   - View recent entries
   - Basic stats (how many repos you've worked on, etc.)

4. **Admin Panel**
   - Import repos from GitHub org
   - Enable/disable which repos show up
   - See system-wide stats

5. **Security**
   - Protect against SQL injection (used prepared statements everywhere)
   - CSRF protection on forms
   - Input validation and sanitization

### Secondary Goals

- Make it responsive (works on mobile)
- Good error handling
- Easy to add features later

---

## System Architecture

Here's how it all works together:

```
User's Browser (HTML/CSS/JavaScript)
         |
         | HTTPS requests
         |
Web Server (Apache with PHP)
         |
    ----|----|----
    |        |    |
GitHub    MySQL  Session
OAuth     DB    Storage
API
```

### The Layers

1. **Frontend (what users see)**
   - HTML pages
   - CSS for styling
   - JavaScript for some interactivity
   - Flatpickr for the date picker (found this library, it's great)

2. **Backend (PHP)**
   - Handles authentication
   - Processes form submissions
   - Talks to the database
   - Makes API calls to GitHub

3. **Database (MySQL)**
   - Stores user info
   - Stores repo info
   - Stores time entries

4. **GitHub API**
   - For OAuth authentication
   - For fetching repo data

I tried to keep things separated - config in one place, database stuff separate from business logic. Not perfect MVC but it works.

---

## Technology Stack

### What I Used

**Backend:**
- PHP 8.0+ (it's what I know best, and XAMPP made local dev easy)
- MySQL 8.0 (database)
- PDO (for database stuff - safer than mysqli)
- cURL (for API calls to GitHub)

**Frontend:**
- HTML5, CSS3, JavaScript
- Flatpickr (for the date picker - really nice library)
- Google Fonts (Inter font - looks clean)

**Services:**
- GitHub OAuth 2.0 (for login)
- GitHub REST API (for getting repo info)

**Tools:**
- XAMPP (for local development)
- phpMyAdmin (for database stuff)
- VS Code/Cursor (for coding)

I kept it simple - no fancy frameworks or build tools. Just vanilla PHP, HTML, CSS, and some JavaScript.

---

## System Features

### 1. Authentication with GitHub

When you click "Login", it:
1. Generates a random state token (for security)
2. Sends you to GitHub to authorize
3. GitHub sends you back with a code
4. The app exchanges the code for an access token
5. Fetches your GitHub profile info
6. Creates/updates your account in the database
7. Logs you in

The tricky part was understanding OAuth flow - took me a while to get the state parameter right and handle all the edge cases. But it works now!

### 2. Logging Hours

You can:
- Pick a repo from a dropdown (only shows active repos)
- Select a date (calendar popup)
- Enter hours (between 0.25 and 16 hours per day)
- Add a description (optional)

The form validates everything on the server side. I had some client-side validation too but you can't trust that - always validate server-side.

### 3. Dashboard

Shows:
- Your total hours logged
- How many entries you've made
- How many different repos you've worked on
- Your most recent 10 entries

Pretty basic but useful. I wanted to add charts but ran out of time.

### 4. Personal History

You can see all your entries, filter by repo, search, paginate through results. Basic CRUD stuff.

### 5. Admin Dashboard

Only accessible to admins (me and Adam for now). Admins can:
- Import all repos from the GitHub org (one click)
- Toggle which repos are active/visible
- See system stats

The import feature was fun to build - it fetches all repos from GitHub, checks if they exist in the DB, updates or inserts as needed.

### 6. Public Repo Showcase

On the homepage, anyone can browse active repos, search them, see contributor stats. No login required for this.

---

## Database Design

I have 3 main tables:

### Users Table
Stores GitHub user info - their GitHub ID, username, name, email, avatar URL, and whether they're an admin.

### Applications Table
Stores repository info - name, description, language, whether it's active, etc. This gets populated from the GitHub import.

### Hours Table
The actual time entries - links to a user and an application, has a date, duration, and optional description.

I used foreign keys with cascade delete - if a user or repo gets deleted, their hours get deleted too. Seems safer that way.

I added some indexes on commonly queried fields (user_id + date, application_id + date) to speed things up.

### Database Schema

The complete schema is in `database/schema.sql`. Here's what the tables look like:

```sql
-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    github_id INT UNIQUE NOT NULL,
    login VARCHAR(255) NOT NULL,
    name VARCHAR(255),
    email VARCHAR(255),
    avatar_url TEXT,
    html_url TEXT,
    is_admin BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Applications (repositories) table
CREATE TABLE applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    github_id INT UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    description TEXT,
    html_url TEXT,
    language VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Hours (time entries) table
CREATE TABLE hours (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    application_id INT NOT NULL,
    work_date DATE NOT NULL,
    duration DECIMAL(4,2) NOT NULL CHECK (duration >= 0.25 AND duration <= 16.00),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    INDEX idx_user_date (user_id, work_date),
    INDEX idx_application_date (application_id, work_date)
);
```

---

## Implementation Details

### Authentication Flow

The OAuth flow was the hardest part. I had to:
1. Generate a secure random state token
2. Store it in the session
3. Redirect to GitHub
4. When GitHub redirects back, check the state matches
5. Exchange the code for a token
6. Use the token to get user info
7. Save everything to the database

Took me a few tries to get it right. The state parameter is crucial for preventing CSRF attacks - I learned that the hard way when testing.

Here's the basic flow:
```php
// Generate state token
$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;

// Redirect to GitHub
$github_url = 'https://github.com/login/oauth/authorize?' . http_build_query([
    'client_id' => GITHUB_CLIENT_ID,
    'redirect_uri' => GITHUB_REDIRECT_URI,
    'scope' => 'user:email',
    'state' => $state
]);
header("Location: $github_url");
```

Then when GitHub sends the user back, I check the state matches and exchange the code for a token.

### Time Entry Validation

I validate:
- CSRF token (must match session)
- Repository ID (must exist and be active)
- Date (must be valid, can't be in the future)
- Duration (between 0.25 and 16 hours)
- Description is optional but gets sanitized

All validation happens server-side. Never trust the client.

### Repo Import

This was cool to build. It:
1. Makes a request to GitHub API: `/orgs/BrickMMO/repos`
2. Gets back a JSON array of all repos
3. Loops through each one
4. Checks if it exists in DB (by GitHub ID)
5. Updates if exists, inserts if new
6. Sets language, visibility, etc.

I ran into rate limiting issues at first - GitHub only lets you make 60 requests per hour without a token. I added a GitHub Personal Access Token to the `.env` file to increase that.

### Database Queries

I used PDO prepared statements everywhere. No string concatenation in SQL - learned that lesson in security class. All user input goes through prepared statements.

Example:
```php
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
```

I used JOINs to get user names with entries, repo names with entries, etc. Had to look up the syntax a few times but got the hang of it.

---

## Security Features

### CSRF Protection

Every form has a CSRF token. I generate a random token, store it in the session, and check it matches on submit. Prevents cross-site request forgery attacks.

```php
function generateCSRFToken() {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}
```

### SQL Injection Prevention

Used PDO prepared statements everywhere. Never build SQL with string concatenation. All user input gets bound as parameters.

### Input Sanitization

I sanitize all user input with `htmlspecialchars()` before displaying. This prevents XSS attacks.

```php
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}
```

### Session Security

Sessions timeout after 1 hour of inactivity. On login, I regenerate the session ID. On logout, I destroy the session completely.

### Admin Access

Only specific users (checked by name/login) can access admin features. Not the most scalable solution but works for now. In production, I'd probably have a proper roles table.

---

## User Interface Design

I tried to make it clean and simple. Used the BrickMMO orange color (#DD5A3A) as the primary color since that's the brand color.

### Design Decisions

- **Responsive:** Works on mobile, tablet, desktop. Used CSS media queries.
- **Clean forms:** Simple inputs, clear labels, helpful error messages
- **Navigation:** Simple menu bar, shows where you are
- **Feedback:** Success/error messages when you submit forms

I'm not a designer, so I kept it simple. Used Inter font from Google Fonts - looks modern and clean.

The date picker (Flatpickr) is really nice - users can click a calendar icon and pick dates visually.

### Color Scheme

- Primary: #DD5A3A (BrickMMO Orange)
- Background: #FDF6F3 (Light Beige)
- Text: #2C3E50 (Dark Gray)
- Accent: #E8D5CF (Light Orange)

Nothing fancy, just clean and readable.

---

## Testing

### How I Tested

I tested manually - no automated tests (didn't have time to learn PHPUnit). I:

1. **Tested authentication:** Login, logout, session timeout
2. **Tested time logging:** Valid entries, invalid entries, edge cases
3. **Tested admin features:** Import repos, toggle repos
4. **Tested security:** Tried SQL injection attempts (they failed!), tested CSRF tokens
5. **Tested on different devices:** Desktop, tablet, phone

### What I Found

- Had some bugs with session handling at first (fixed now)
- Date validation needed tweaking (rejecting valid dates initially)
- Pagination had an off-by-one error (classic mistake)
- Admin check wasn't working consistently (fixed by checking both name and login)

Most bugs were small logic errors. Nothing major, but lots of little fixes.

### Performance

I didn't do formal performance testing, but:
- Pages load pretty quickly (under 2 seconds usually)
- Database queries are fast (under 100ms for most queries)
- GitHub API calls take a bit longer (300-500ms)

Could probably optimize more, but it works fine for now.

---

## Deployment Guide

### For Local Development

I used XAMPP:
1. Install XAMPP
2. Put project in `htdocs` folder
3. Create database in phpMyAdmin
4. Run `database/schema.sql` to create tables
5. Copy `.env.example` to `.env` and fill in your values
6. Start Apache and MySQL
7. Go to `http://localhost/http5225/Testingphp/`

### For Production (InfinityFree)

I'm deploying this to InfinityFree. Steps:

1. Upload all files via FTP
2. Create database in InfinityFree control panel
3. Update `.env` file with InfinityFree database credentials:
   - DB_HOST (usually something like `sqlXXX.infinityfree.com`)
   - DB_NAME (usually `username_dbname`)
   - DB_USER (usually `username_dbuser`)
   - DB_PASS (the password InfinityFree gives you)
4. Set BASE_URL to your domain
5. Set DEVELOPMENT=false
6. Make sure `.env` file isn't publicly accessible

The `.env` file approach makes it easy to switch between local and production without changing code. Much better than hardcoding values.

### GitHub OAuth Setup

You need to:
1. Go to GitHub Settings → Developer settings → OAuth Apps
2. Create a new OAuth App
3. Set the callback URL to your production URL + `/auth/callback.php`
4. Copy the Client ID and Secret to your `.env` file

For local dev, use `http://localhost/http5225/Testingphp/auth/callback.php`. For production, use your actual domain.

### GitHub Token Setup

To avoid rate limiting on GitHub API calls:
1. Go to GitHub Settings → Developer settings → Personal access tokens
2. Generate a new token with `repo` and `read:org` scopes
3. Add it to your `.env` file as `GITHUB_TOKEN=your_token_here`

Without this, you'll hit rate limits pretty quickly when importing repos.

---

## Future Improvements

If I had more time, I'd add:

1. **Better analytics:** Charts showing hours over time, breakdown by repo, etc. Maybe use Chart.js or similar.

2. **Export functionality:** Export your data to CSV or PDF. Would be useful for reports.

3. **Better admin features:** User management, bulk operations, better stats.

4. **Email notifications:** Maybe reminders to log hours, or weekly summaries.

5. **Mobile app:** Native app would be nice, but that's a whole other project.

6. **Unit tests:** Learn PHPUnit and write proper tests. Would catch bugs earlier.

7. **Better error handling:** More graceful error messages, better error pages.

8. **Caching:** Cache GitHub API responses to avoid rate limits. Maybe Redis.

Some of this is in the "nice to have" category. The core functionality works for what we need right now.

---

## What I Learned

This project taught me a lot:

1. **OAuth is complex** - But really powerful once you understand it. The state parameter is crucial.

2. **Security matters** - Prepared statements aren't optional. CSRF protection is important. Input sanitization everywhere.

3. **API rate limits are real** - Hit GitHub's rate limits a few times. Adding a token helped.

4. **Database design matters** - Spent time thinking about relationships, indexes, constraints. Worth it.

5. **Environment variables are great** - Using `.env` makes deployment so much easier. No more hardcoding secrets.

6. **Testing is important** - Wish I'd written tests, but manual testing found a lot of bugs.

7. **Documentation is hard** - Writing this README took almost as long as some features.

8. **Don't overthink it** - Started with something simple, added features as needed. Better than trying to build everything at once.

Biggest challenge was understanding OAuth flow and GitHub's API. But once I got it, things clicked.

Biggest success was getting the whole thing working end-to-end. Seeing someone else use it and log hours was pretty cool.

---

## References

I used these resources a lot:

1. **PHP Manual** - https://www.php.net/manual/ (especially the PDO section)

2. **GitHub API Docs** - https://docs.github.com/en/rest (for API endpoints)

3. **GitHub OAuth Docs** - https://docs.github.com/en/apps/oauth-apps (for OAuth flow)

4. **Stack Overflow** - For specific problems and syntax questions

5. **OWASP Top 10** - For security best practices

6. **Flatpickr Docs** - https://flatpickr.js.org/ (for the date picker)

7. **MySQL Documentation** - For SQL syntax and constraints

Stack Overflow helped a lot with specific issues. PHP.net manual for PDO syntax. GitHub docs for API endpoints and OAuth flow.

---

## License

This is part of the BrickMMO project. All rights reserved.

---

## Contact

**Developer:** Jashanpreet Singh Gill  
**Institution:** Humber College  
**GitHub:** [your-github-username]

---

**Last Updated:** November 2025  
**Status:** Capstone Project Submission

Thanks for reading! If you have questions or suggestions, feel free to reach out.