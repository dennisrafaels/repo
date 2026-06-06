# Database Design

This document covers the full schema design process for ActivityHub, from ERD through to the final MariaDB tables.

---

## 1. Entity-Relationship Diagram (ERD)

Three entities were identified:

- **Category** — classifies activities (Sports, Study, Social, etc.)
- **Activity** — a campus event created by a user
- **RSVP** — a participant joining an activity

### Relationships

| Relationship | Type |
|---|---|
| Category → Activity | One-to-Many (optional) |
| Activity → RSVP | One-to-Many |

```
[categories] ||--o{ [activities] : "categorizes"
[activities] ||--o{ [rsvps]      : "has"
```

---

## 2. Relational Model

### categories
| Attribute | Type | Constraint |
|---|---|---|
| id | INT | PK, AUTO_INCREMENT |
| name | VARCHAR(50) | NOT NULL |
| color | VARCHAR(7) | NOT NULL, DEFAULT '#4CAF50' |
| icon | VARCHAR(10) | NOT NULL, DEFAULT '📌' |

### activities
| Attribute | Type | Constraint |
|---|---|---|
| id | INT | PK, AUTO_INCREMENT |
| title | VARCHAR(100) | NOT NULL |
| description | TEXT | nullable |
| category_id | INT | FK → categories(id), ON DELETE SET NULL |
| location | VARCHAR(100) | nullable |
| start_datetime | DATETIME | NOT NULL |
| end_datetime | DATETIME | NOT NULL |
| max_participants | INT | nullable (NULL = unlimited) |
| created_by | VARCHAR(50) | NOT NULL |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP |

### rsvps
| Attribute | Type | Constraint |
|---|---|---|
| id | INT | PK, AUTO_INCREMENT |
| activity_id | INT | FK → activities(id), ON DELETE CASCADE |
| participant_name | VARCHAR(50) | NOT NULL |
| participant_email | VARCHAR(100) | nullable |
| joined_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP |
| — | UNIQUE | (activity_id, participant_name) |

---

## 3. Third Normal Form (3NF) Analysis

### 1NF — First Normal Form ✅
- All attributes are atomic (no repeating groups or arrays)
- Each table has a primary key

### 2NF — Second Normal Form ✅
- All non-key attributes are fully dependent on the whole primary key
- No partial dependencies (all PKs are single-column)

### 3NF — Third Normal Form ✅
- No transitive dependencies exist
- `category_id` in `activities` references `categories(id)` directly — the category name, color, and icon are stored in `categories`, not duplicated in `activities`
- `activity_id` in `rsvps` references `activities(id)` directly — no transitive dependency on activity details

---

## 4. Final RDB Tables (MariaDB)

```sql
CREATE TABLE categories (
    id    INT AUTO_INCREMENT PRIMARY KEY,
    name  VARCHAR(50)  NOT NULL,
    color VARCHAR(7)   NOT NULL DEFAULT '#4CAF50',
    icon  VARCHAR(10)  NOT NULL DEFAULT '📌'
);

CREATE TABLE activities (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    title            VARCHAR(100) NOT NULL,
    description      TEXT,
    category_id      INT,
    location         VARCHAR(100),
    start_datetime   DATETIME     NOT NULL,
    end_datetime     DATETIME     NOT NULL,
    max_participants INT          DEFAULT NULL,
    created_by       VARCHAR(50)  NOT NULL,
    created_at       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

CREATE TABLE rsvps (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    activity_id       INT         NOT NULL,
    participant_name  VARCHAR(50) NOT NULL,
    participant_email VARCHAR(100),
    joined_at         TIMESTAMP   DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_rsvp (activity_id, participant_name),
    FOREIGN KEY (activity_id) REFERENCES activities(id) ON DELETE CASCADE
);
```

---

## 5. Design Decisions

- `category_id` uses `ON DELETE SET NULL` — deleting a category does not delete its activities, they just become uncategorized
- `activity_id` uses `ON DELETE CASCADE` — deleting an activity also removes all its RSVPs
- `UNIQUE KEY (activity_id, participant_name)` prevents duplicate RSVPs from the same person
- `max_participants = NULL` means unlimited capacity
