# Carbon Footprint Tracker

A personal energy and transportation monitoring application that helps users track, analyze, and reduce their carbon footprint.

# Figma Design

https://www.figma.com/design/eVUq3rvePlqaik74cehc5p/WPL_Project?node-id=0-1&t=icGgMgECmL7oiy0x-1

## Features

- **User Authentication**: Secure login and registration system
- **Energy Usage Tracking**: Log daily household energy consumption
- **Transport Monitoring**: Record transportation methods and distances
- **Carbon Footprint Calculation**: Automatic calculation based on energy and transport data
- **Personalized Recommendations**: Get tailored suggestions to reduce your carbon footprint
- **Visual Reports**: View your progress with charts and detailed breakdowns
- **Data Export**: Download your carbon footprint report as a text file

## Technologies Used

- PHP 7.4+
- MySQL Database
- HTML5, CSS3, JavaScript
- Chart.js for data visualization
- Responsive design for mobile and desktop

## Installation

### Prerequisites

- XAMPP (with PHP 7.4 or higher)
- MySQL database (included with XAMPP)
- Web browser

### Setup Instructions

1. Clone the repository to your XAMPP htdocs folder.

2. Set up the MySQL database:
* Navigate to http://localhost/your-folder-name/db/create_database.php to create the database
* Run the setup script by visiting http://localhost/your-folder-name/db/setup.php to create tables and add sample data

3. Access the application:
* Visit http://localhost/your-folder-name/index.php in your web browser
* Register a new account or log in with existing credentials

Note: Replace "your-folder-name" with whatever directory name you used when installing the application.

## Usage

1. **Register/Login**: Create an account or log in to your existing account
2. **Dashboard**: View your carbon footprint summary and quick recommendations
3. **Log Energy**: Record your daily energy consumption
4. **Log Transport**: Track your transportation methods and distances
5. **View Report**: Generate detailed reports and get personalized recommendations
6. **Reduce Footprint**: Follow suggestions to decrease your carbon emissions

## Database Structure

The application uses the following main tables:
- `users`: Stores user account information
- `energy_usage`: Tracks daily energy consumption
- `user_transport`: Records transportation data
- `transport_modes`: Contains different transportation methods and their emission factors
- `appliances`: Lists common household appliances and their power ratings
- `user_appliances`: Tracks which appliances users have and their usage

## Project Structure

- `/api` - Backend API endpoints for data processing
- `/db` - Database configuration and setup files
- `/includes` - Reusable PHP components like header and footer
- `/views` - Frontend page templates

## Acknowledgements

- [Chart.js](https://www.chartjs.org/) for data visualization
- [XAMPP](https://www.apachefriends.org/) for the local development environment
