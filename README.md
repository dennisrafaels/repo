# ActivityHub — Campus Activity Calendar

ActivityHub is a campus activity calendar web application that allows students to create, discover, and join activities. From study groups to sports games, everything is in one place with real-time RSVP tracking and category filters.

## Features

- Browse activities on a monthly calendar view
- Create, edit, and delete activities
- RSVP / join activities with your name
- Filter activities by category
- View team member profiles
- Fully server-side rendered with PHP

## Tech Stack

- **Server:** Apache 2.4
- **Backend:** PHP 8.4
- **Database:** MariaDB 11.8
- **Hardware:** Raspberry Pi Zero 2W
- **OS:** DietPi (Debian-based)

## Project Structure

```
repo/
├── index.php           # Main calendar page (server-side rendered)
├── team.php            # Team member introduction page
├── php/
│   ├── config.php      # Database connection configuration
│   └── api.php         # JSON API endpoint (CRUD + RSVP)
├── sql/
│   └── schema.sql      # Database schema and seed data
├── README.md
├── Contributors.md
├── Installation.md
├── UserGuide.md
└── AdminGuide.md
```

## Documentation

- [Contributors](Contributors.md)
- [Installation Guide](Installation.md)
- [User Guide](UserGuide.md)
- [Admin Guide](AdminGuide.md)
