# Event Organiser REST API

**version 0.1.0-alpha**

## Endpoints

The endpoints listed below assume a base of `<yoursite>/wp-json/wp/v2`

* * *

### /events
**Retrieve an array of events**
```
GET /wp-json/wp/v2/events
```
This will return occurrences, that is an event (post) object supplemented with an (occurrence) ID, start and end datetime (for that occurrence)

**Create a new event**
```
POST /wp-json/wp/v2/events
```

* * *

### /events/:event_id
**Retrieve a particular event**
```
GET /wp-json/wp/v2/events/:event_id
```
This returns an event (post) object.

**Update an existing event**
```
POST /wp-json/wp/v2/events/:event_id
```
Update an existing event (post) object.

**Delete an event (an all its occurrences)**
```
DELETE /wp-json/wp/v2/events/:event_id
```

* * * 

### /events/:event_id/occurrences
**Retrieve all occurrences of a given event**
```
GET /wp-json/wp/v2/events/:event_id/occurrences
```

* * * 

### /events/:event_id/occurrences/:occurrence_id
**Retrieve a particular occurrence of a given event**
```
GET /wp-json/wp/v2/events/:event_id/occurrences/:occurrence_id
```

* * * 

### /event-venues/?event=:event_id
**Retrieve an event's venue(s)**
```
GET /wp-json/wp/v2/event-venues/?event=:event_id
```

Although events only have one venue, this will still return an array

* * * 

### /event-categories/?event=:event_id
**Retrieve an event's categories**
```
GET /wp-json/wp/v2/event-categories/?event=:event_id
```

* * * 

### /event-tags/?event=:event_id
**Retrieve an event's tags**
```
GET /wp-json/wp/v2/event-tags/?event=:event_id
```

* * *

### /event-venues
**Retrieve an array of venues**
```
GET /wp-json/wp/v2/event-venue
```
Returns all venues 

**Create a new venue**
```
POST /wp-json/wp/v2/event-venues
```

* * *

### /event-venue/:event-venue-id
**Retrieve a particular event venue**
```
GET /wp-json/wp/v2/event-venues/:event-venue-id
```
This returns an event (post) object.

**Update an existing event venue**
```
POST /wp-json/wp/v2/event-venues/:event-venue-id
```
Update an existing event (post) object.

**Delete an event (an all its occurrences)**
```
DELETE /wp-json/wp/v2/event-venues/:event-venue-id
```

* * *

### /event-categories
**Retrieve an array of categories**
```
GET /wp-json/wp/v2/event-categories
```
Returns all categories 

**Create a new category**
```
POST /wp-json/wp/v2/event-categories
```

* * *

### /event-categories/:event-category-id
**Retrieve a particular event category**
```
GET /wp-json/wp/v2/event-categories/:event-category-id
```
This returns an event category (term) object.

**Update an existing event category**
```
POST /wp-json/wp/v2/event-categories/:event-category-id
```
Update an existing event category (term) object.

**Delete an event category**
```
DELETE /wp-json/wp/v2/event-categories/:event-category-id
```

* * *

### /event-tags
**Retrieve an array of tags**
```
GET /wp-json/wp/v2/event-tags
```
Returns all tags 

**Create a new tag**
```
POST /wp-json/wp/v2/event-tags
```

* * *

### /event-tag/:event-tag-id
**Retrieve a particular event tag**
```
GET /wp-json/wp/v2/event-tags/:event-tag-id
```
This returns an event tag (term) object.

**Update an existing event tag**
```
POST /wp-json/wp/v2/event-tags/:event-tag-id
```
Update an existing event tag (term) object.

**Delete an event tag**
```
DELETE /wp-json/wp/v2/event-tags/:event-tag-id
```

* * * 

