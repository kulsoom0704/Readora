Readora Project Documentation
Readora – Laravel Book Club Platform Documentation
1. Project Overview

Project Name: Readora
Framework: Laravel
Project Type: Book Club / Book Management Website

Readora is a web platform where users can discover books, browse categories, manage profiles, and interact with the system through a secure login system. The platform contains two user roles:

Regular User
Admin

Admins have additional permissions such as managing users and assigning admin rights.

2. Functionalities
Authentication System

Visitors can:
Register a new account
Log in to their account
Log out securely

The authentication system is built using Laravel authentication features.

User Roles
Regular User
A regular user can:
View books
Browse categories
Edit their profile
Access their dashboard

Admin:
An admin can:
Promote users to admin
Remove admin rights
Manage users
Access admin pages
Admin routes are protected using middleware
Username: admin
Email: admin@ehb.be
Password: Password!321

Profile System:
Each user has:
Name
Email
Password
delete account
Users can update their profile information.

Book Categories:
Books are divided into categories such as:
Fantasy
Romance
Science Fiction
Mystery
Horror
History
Categories help users easily browse books.

home page:
After logging in, users are redirected to home page where they can:
Navigate through the website
Access profile settings
Browse books and categories

3. Technologies Used
Technology	        Purpose
PHP	                Backend programming language
Laravel	            MVC Framework
Blade	            Frontend templating engine
HTML/CSS	        Frontend structure and styling
Bootstrap/Tailwind	UI styling
Laravel Herd	    Local development environment
Git & GitHub	    Version control

4. MVC Structure

The project follows the MVC architecture:

Model:
Handles database interaction and logic.
Examples:
User model
Category model
Book model

View:
Handles the frontend using Blade templates.
Examples:
home page
Profile page
Category page

Controller:
Handles application logic between models and views.
Examples:
ProfileController
CategoryController
AdminController

5. Database Structure
Users Table
Contains:
id
name
email
password
is_admin

Categories Table:
Contains:
id
name

Books Table:
Contains:
id
title
author
description
category_id

6. Middleware Protection
Admin routes are protected with middleware to prevent normal users from accessing admin functionalities.
Example protected route:
Route::middleware(['admin'])->group(function () {
    Route::get('/admin/users', [AdminController::class, 'index']);
});

7. Security Features:
The project includes:
Password hashing
Authentication protection
Middleware authorization
CSRF protection
Form validation

8. How to Run the Project
Requirements
PHP installed
Composer installed
Laravel Herd or XAMPP
MySQL database

9. Challenges Faced
During development, some challenges included:
Understanding Laravel MVC structure
Setting up middleware correctly
Protecting admin routes
Learning Blade templates
Git and GitHub integration
These problems were solved through testing and debugging.

10. What I Learned:
Through this project, I learned:
Laravel basics
MVC architecture
Authentication systems
Route protection with middleware
Database migrations
Git and GitHub usage
Blade templating
Basic full-stack web development

11. Conclusion:
Readora is a Laravel-based book club platform created to practice full-stack web development concepts. The project demonstrates authentication systems, role management, MVC architecture, middleware security, and database relationships using Laravel.
The project helped improve backend development skills and understanding of modern web development workflows.