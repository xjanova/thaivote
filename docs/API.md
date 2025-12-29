# ThaiVote API Documentation

## Overview

ThaiVote provides a RESTful API for accessing election data, results, news, and party information.

**Base URL:** `https://your-domain.com/api/v1`

**Authentication:** Most endpoints are public. Admin endpoints require Laravel Sanctum token.

---

## Authentication

### Login

```http
POST /auth/login
```

**Request Body:**
```json
{
    "email": "admin@thaivote.com",
    "password": "your-password"
}
```

**Response:**
```json
{
    "token": "1|abc123...",
    "user": {
        "id": 1,
        "name": "Admin",
        "email": "admin@thaivote.com",
        "role": "admin"
    }
}
```

### Logout

```http
POST /auth/logout
Authorization: Bearer {token}
```

---

## Elections

### List Elections

```http
GET /elections
```

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `status` | string | Filter by status: `upcoming`, `active`, `completed` |
| `type` | string | Filter by type: `general`, `senate`, `local`, `referendum` |
| `per_page` | integer | Items per page (default: 15) |

**Response:**
```json
{
    "data": [
        {
            "id": 1,
            "name": "การเลือกตั้งทั่วไป พ.ศ. 2566",
            "name_en": "2023 General Election",
            "type": "general",
            "election_date": "2023-05-14",
            "status": "completed",
            "total_constituencies": 400,
            "total_eligible_voters": 52000000,
            "results_progress": 100.0
        }
    ],
    "meta": {
        "current_page": 1,
        "last_page": 1,
        "per_page": 15,
        "total": 1
    }
}
```

### Get Election

```http
GET /elections/{id}
```

**Response:**
```json
{
    "data": {
        "id": 1,
        "name": "การเลือกตั้งทั่วไป พ.ศ. 2566",
        "name_en": "2023 General Election",
        "type": "general",
        "election_date": "2023-05-14",
        "status": "completed",
        "total_constituencies": 400,
        "total_eligible_voters": 52000000,
        "total_votes_cast": 39514973,
        "turnout_percentage": 75.99,
        "results_progress": 100.0,
        "created_at": "2023-05-14T00:00:00Z",
        "updated_at": "2023-05-15T23:59:59Z"
    }
}
```

### Get Election Results Summary

```http
GET /elections/{id}/results
```

**Response:**
```json
{
    "data": {
        "election_id": 1,
        "total_votes": 39514973,
        "turnout": 75.99,
        "constituencies_reported": 400,
        "constituencies_total": 400,
        "party_results": [
            {
                "party_id": 1,
                "party_name": "ก้าวไกล",
                "party_color": "#FF6600",
                "total_votes": 14438851,
                "vote_percentage": 36.55,
                "constituency_seats": 112,
                "party_list_seats": 39,
                "total_seats": 151
            }
        ],
        "last_updated": "2023-05-15T23:59:59Z"
    }
}
```

---

## Provinces

### List Provinces

```http
GET /provinces
```

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `region` | string | Filter by region: `central`, `north`, `northeast`, `east`, `west`, `south` |
| `include` | string | Include relations: `constituencies`, `results` |

**Response:**
```json
{
    "data": [
        {
            "id": 1,
            "code": "BKK",
            "name_th": "กรุงเทพมหานคร",
            "name_en": "Bangkok",
            "region": "central",
            "constituency_count": 33,
            "population": 5527994
        }
    ]
}
```

### Get Province

```http
GET /provinces/{id}
```

**Response:**
```json
{
    "data": {
        "id": 1,
        "code": "BKK",
        "name_th": "กรุงเทพมหานคร",
        "name_en": "Bangkok",
        "region": "central",
        "constituency_count": 33,
        "population": 5527994,
        "constituencies": [
            {
                "id": 1,
                "number": 1,
                "name": "เขต 1",
                "districts": ["พระนคร", "ป้อมปราบศัตรูพ่าย", "สัมพันธวงศ์", "ดุสิต"]
            }
        ]
    }
}
```

### Get Province Election Results

```http
GET /provinces/{id}/results
```

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `election_id` | integer | Filter by election (default: active election) |

**Response:**
```json
{
    "data": {
        "province_id": 1,
        "province_name": "กรุงเทพมหานคร",
        "election_id": 1,
        "total_votes": 3250000,
        "turnout": 68.5,
        "leading_party": {
            "id": 1,
            "name": "ก้าวไกล",
            "color": "#FF6600",
            "seats_won": 32
        },
        "party_results": [
            {
                "party_id": 1,
                "party_name": "ก้าวไกล",
                "votes": 1800000,
                "vote_percentage": 55.38,
                "seats_won": 32
            }
        ],
        "constituencies": [
            {
                "id": 1,
                "number": 1,
                "winner": {
                    "candidate_id": 1,
                    "name": "สมชาย ใจดี",
                    "party": "ก้าวไกล",
                    "votes": 45000
                },
                "results_status": "official"
            }
        ]
    }
}
```

---

## Constituencies

### List Constituencies

```http
GET /constituencies
```

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `province_id` | integer | Filter by province |
| `election_id` | integer | Include results for election |

### Get Constituency

```http
GET /constituencies/{id}
```

### Get Constituency Results

```http
GET /constituencies/{id}/results
```

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `election_id` | integer | Filter by election |

**Response:**
```json
{
    "data": {
        "constituency_id": 1,
        "constituency_name": "กรุงเทพมหานคร เขต 1",
        "election_id": 1,
        "eligible_voters": 150000,
        "total_votes": 102750,
        "turnout": 68.5,
        "valid_votes": 100000,
        "invalid_votes": 2750,
        "results_status": "official",
        "candidates": [
            {
                "id": 1,
                "candidate_number": 1,
                "name": "สมชาย ใจดี",
                "party_id": 1,
                "party_name": "ก้าวไกล",
                "party_color": "#FF6600",
                "votes": 45000,
                "vote_percentage": 45.0,
                "is_winner": true
            }
        ],
        "last_updated": "2023-05-15T23:59:59Z"
    }
}
```

---

## Parties

### List Parties

```http
GET /parties
```

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `election_id` | integer | Include results for election |
| `sort` | string | Sort by: `name`, `votes`, `seats` |

**Response:**
```json
{
    "data": [
        {
            "id": 1,
            "name_th": "พรรคก้าวไกล",
            "name_en": "Move Forward Party",
            "abbreviation": "MFP",
            "color": "#FF6600",
            "logo_url": "/storage/parties/mfp.png",
            "leader": "พิธา ลิ้มเจริญรัตน์",
            "founded_year": 2020,
            "website": "https://moveforwardparty.org"
        }
    ]
}
```

### Get Party

```http
GET /parties/{id}
```

### Get Party Election Results

```http
GET /parties/{id}/results
```

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `election_id` | integer | Filter by election |

**Response:**
```json
{
    "data": {
        "party_id": 1,
        "party_name": "ก้าวไกล",
        "election_id": 1,
        "total_votes": 14438851,
        "vote_percentage": 36.55,
        "constituency_seats": 112,
        "party_list_seats": 39,
        "total_seats": 151,
        "province_results": [
            {
                "province_id": 1,
                "province_name": "กรุงเทพมหานคร",
                "votes": 1800000,
                "seats_won": 32
            }
        ]
    }
}
```

---

## Candidates

### List Candidates

```http
GET /candidates
```

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `election_id` | integer | Filter by election |
| `party_id` | integer | Filter by party |
| `constituency_id` | integer | Filter by constituency |
| `type` | string | Filter by type: `constituency`, `party_list` |

### Get Candidate

```http
GET /candidates/{id}
```

**Response:**
```json
{
    "data": {
        "id": 1,
        "candidate_number": 1,
        "full_name": "สมชาย ใจดี",
        "type": "constituency",
        "party": {
            "id": 1,
            "name": "ก้าวไกล",
            "color": "#FF6600"
        },
        "constituency": {
            "id": 1,
            "name": "กรุงเทพมหานคร เขต 1"
        },
        "photo_url": "/storage/candidates/1.jpg",
        "education": "ปริญญาโท รัฐศาสตร์",
        "experience": "อดีต สส. 2 สมัย"
    }
}
```

---

## News

### List News Articles

```http
GET /news
```

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `category` | string | Filter: `election`, `politics`, `analysis` |
| `source` | string | Filter by source |
| `party_id` | integer | Filter by mentioned party |
| `from` | date | Published after date |
| `to` | date | Published before date |
| `per_page` | integer | Items per page |

**Response:**
```json
{
    "data": [
        {
            "id": 1,
            "title": "ผลการเลือกตั้ง 2566 อย่างไม่เป็นทางการ",
            "excerpt": "ก้าวไกลนำ 151 ที่นั่ง ตามด้วยเพื่อไทย 141 ที่นั่ง",
            "content": "...",
            "source": {
                "name": "Thai PBS",
                "url": "https://thaipbs.or.th"
            },
            "category": "election",
            "sentiment": "neutral",
            "ai_summary": "...",
            "mentioned_parties": [1, 2],
            "published_at": "2023-05-14T20:00:00Z",
            "image_url": "/storage/news/1.jpg"
        }
    ]
}
```

### Get News Article

```http
GET /news/{id}
```

---

## Real-time Updates

### WebSocket Connection

Connect to Laravel Reverb for real-time updates:

```javascript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: process.env.VITE_REVERB_APP_KEY,
    wsHost: process.env.VITE_REVERB_HOST,
    wsPort: process.env.VITE_REVERB_PORT,
    forceTLS: false,
    enabledTransports: ['ws', 'wss'],
});
```

### Available Channels

#### Public Channel: `election.{id}`

```javascript
Echo.channel('election.1')
    .listen('ResultsUpdated', (e) => {
        console.log('Results updated:', e.results);
    })
    .listen('TurnoutUpdated', (e) => {
        console.log('Turnout:', e.turnout);
    });
```

#### Public Channel: `province.{id}`

```javascript
Echo.channel('province.1')
    .listen('ProvinceResultsUpdated', (e) => {
        console.log('Province results:', e.results);
    });
```

#### Public Channel: `news`

```javascript
Echo.channel('news')
    .listen('NewsPublished', (e) => {
        console.log('New article:', e.article);
    });
```

### Event Payloads

**ResultsUpdated:**
```json
{
    "election_id": 1,
    "timestamp": "2023-05-14T20:30:00Z",
    "results": {
        "constituencies_reported": 350,
        "party_results": [...]
    }
}
```

**ProvinceResultsUpdated:**
```json
{
    "province_id": 1,
    "election_id": 1,
    "timestamp": "2023-05-14T20:30:00Z",
    "leading_party_id": 1,
    "results": {...}
}
```

---

## Error Handling

### Error Response Format

```json
{
    "error": {
        "code": "VALIDATION_ERROR",
        "message": "The given data was invalid.",
        "details": {
            "email": ["The email field is required."]
        }
    }
}
```

### HTTP Status Codes

| Code | Description |
|------|-------------|
| 200 | Success |
| 201 | Created |
| 400 | Bad Request |
| 401 | Unauthorized |
| 403 | Forbidden |
| 404 | Not Found |
| 422 | Validation Error |
| 429 | Too Many Requests |
| 500 | Server Error |

### Error Codes

| Code | Description |
|------|-------------|
| `VALIDATION_ERROR` | Request validation failed |
| `AUTHENTICATION_ERROR` | Invalid or missing token |
| `AUTHORIZATION_ERROR` | Insufficient permissions |
| `NOT_FOUND` | Resource not found |
| `RATE_LIMIT_EXCEEDED` | Too many requests |
| `SERVER_ERROR` | Internal server error |

---

## Rate Limiting

- **Public endpoints:** 60 requests per minute
- **Authenticated endpoints:** 120 requests per minute
- **Admin endpoints:** 300 requests per minute

Rate limit headers:
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 58
X-RateLimit-Reset: 1684080000
```

---

## Pagination

All list endpoints support pagination:

```json
{
    "data": [...],
    "links": {
        "first": "https://api.thaivote.com/v1/elections?page=1",
        "last": "https://api.thaivote.com/v1/elections?page=5",
        "prev": null,
        "next": "https://api.thaivote.com/v1/elections?page=2"
    },
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 5,
        "per_page": 15,
        "to": 15,
        "total": 75
    }
}
```

---

## Admin Endpoints

All admin endpoints require authentication with admin role.

### Elections Management

```http
POST   /admin/elections          # Create election
PUT    /admin/elections/{id}     # Update election
DELETE /admin/elections/{id}     # Delete election
POST   /admin/elections/{id}/activate    # Set as active
POST   /admin/elections/{id}/complete    # Mark completed
```

### Results Management

```http
POST   /admin/results/import     # Import results from CSV
POST   /admin/results/scrape     # Trigger scraping job
PUT    /admin/results/{id}       # Update result
POST   /admin/results/verify     # Verify with consensus
```

### News Management

```http
POST   /admin/news              # Create article
PUT    /admin/news/{id}         # Update article
DELETE /admin/news/{id}         # Delete article
POST   /admin/news/aggregate    # Trigger aggregation
```

### Party Management

```http
POST   /admin/parties           # Create party
PUT    /admin/parties/{id}      # Update party
DELETE /admin/parties/{id}      # Delete party
POST   /admin/parties/{id}/logo # Upload logo
```

---

## SDK & Libraries

### JavaScript/TypeScript

```bash
npm install @thaivote/api-client
```

```typescript
import { ThaiVoteClient } from '@thaivote/api-client';

const client = new ThaiVoteClient({
    baseUrl: 'https://api.thaivote.com/v1',
    apiKey: 'your-api-key' // Optional for public endpoints
});

// Get election results
const results = await client.elections.getResults(1);

// Subscribe to real-time updates
client.subscribe('election.1', (event) => {
    console.log('Update:', event);
});
```

### PHP

```bash
composer require thaivote/api-client
```

```php
use ThaiVote\Client;

$client = new Client([
    'base_url' => 'https://api.thaivote.com/v1',
]);

$elections = $client->elections()->list();
$results = $client->elections()->getResults(1);
```

---

*For implementation details, see [DEVELOPMENT.md](./DEVELOPMENT.md)*
