# API for the Drop of Life application

### Heroku API URL
http://shielded-hollows-19820.herokuapp.com

### Routes
Calling each of these routes will return a JSON. On error, the JSON will only contain an _error_ key with the associated error message.
* ### /signup
  **Role:** Create a new user account

  **Request type:** POST

  **Request body:**
    * **username** - _mandatory_. Must be alphanumeric (0-9, a-z, A-Z)
    * **email** - _mandatory_. Must be an email (something@somewhere.com)
    * **password** - _mandatory_. Will be hashed when placed in database
    * **type** - _mandatory_
    * **blood_type**
    * **hospital**

  **Return on success**
    * {'**message**': 'User added successfully!'}

* ### /login
  **Role:** Logs the user in and returns the token associated to the user.

  **Request type:** POST

  **Request body:**
    * **username** - _mandatory_. Must be alphanumeric (0-9, a-z, A-Z)
    * **password** - _mandatory_

  **Return on success**
    * {'**token**': '<_user token_>'} -> token is a 20-character string

* ### /logout_all
  **Role:** Logs the user out of all devices, by removing his associated token from the database.

  **Request type:** POST

  **Request body:**
    * **token** - _mandatory_. The user token received when logging in.

  **Return on success**
    * {'**message**': 'Logout successful!'}

* ### /add_donation
  **Role:** Adds a donation to the database. User must be logged in and must be a doctor.

  **Request type:** POST

  **Request body:**
    * **token** - _mandatory_. The user token received when logging in.
    * **name** - _mandatory_. Donation name.
    * **requested_quantity** - _mandatory_. The quantity of blood that the receiver needs, in litres.
    * **blood_type** - _mandatory_. The necessary blood type.

  **Return on success**
    * {'**message**': 'Donation added successfully!'}

* ### /edit_donation
  **Role:** Edits a donation in the database. User must be logged in and must be either a "doctor", an "admin" or a "center". Donation must exist in the database.

  **Request type:** POST

  Donation will be identified by **ID only**! The other fields represent the new values that will replace the donation's fields.
  **Request body:**
    * **token** - _mandatory_. The user token received when logging in.
    * **donation_id** - _mandatory_. The id of the donation that will be changed.
    * **name** - _mandatory_. The new donation name.
    * **hospital** - _mandatory_. The new hospital name.
    * **requested_quantity** - _mandatory_. The new quantity of blood that the receiver needs, in litres.
    * **blood_type** - _mandatory_. The new necessary blood type.

  **Return on success**
    * {'**message**': 'Donation edited successfully!'}

* ### /available_donations
  **Role:** Get all donations from database

  **Request type:** GET

  **Return on success**
    * JSON array of donations, that looks like this:
      ```json
      [
        {
          "id": 1,
          "name": "Fatau Jigi",
          "requested_quantity": 40.5,
          "existing_quantity": 0,
          "donations_count": 0,
          "hospital": "Floreasca",
          "blood_type": "AB",
          "creation_date": {
            "date": "2018-12-03 00:00:00.000000",
            "timezone_type": 3,
            "timezone": "Europe/Helsinki"
          },
          "last_donation_date": null
        },
        {
          ...
        }, ...
      ]
      ```

* ### /available_donations/blood_type/{blood_type}
  **Role:** Get all donations from database that match the specified blood type

  **Request type:** GET

  **Return on success**
    * JSON array of donations, that looks like this:
      ```json
      [
        {
          "id": 1,
          "name": "Fatau Jigi",
          "requested_quantity": 40.5,
          "existing_quantity": 0,
          "donations_count": 0,
          "hospital": "Floreasca",
          "blood_type": "AB",
          "creation_date": {
            "date": "2018-12-03 00:00:00.000000",
            "timezone_type": 3,
            "timezone": "Europe/Helsinki"
          },
          "last_donation_date": null
        },
        {
          ...
        }, ...
      ]
      ```

* ### /available_donations/hospital
  **Role:** Get all donations from database that match the specified hospital

  **Request type:** POST

  **Request body:**
    * **hospital** - _mandatory_. The hospital to search for.

  **Return on success**
    * JSON array of donations, that looks like this:
      ```json
      [
        {
          "id": 1,
          "name": "Fatau Jigi",
          "requested_quantity": 40.5,
          "existing_quantity": 0,
          "donations_count": 0,
          "hospital": "Floreasca",
          "blood_type": "AB",
          "creation_date": {
            "date": "2018-12-03 00:00:00.000000",
            "timezone_type": 3,
            "timezone": "Europe/Helsinki"
          },
          "last_donation_date": null
        },
        {
          ...
        }, ...
      ]
      ```

* ### /available_donations/name
  **Role:** Get all donations from database that match the specified name

  **Request type:** POST

  **Request body:**
    * **name** - _mandatory_. The name to search for.

  **Return on success**
    * JSON array of donations, that looks like this:
      ```json
      [
        {
          "id": 1,
          "name": "Fatau Jigi",
          "requested_quantity": 40.5,
          "existing_quantity": 0,
          "donations_count": 0,
          "hospital": "Floreasca",
          "blood_type": "AB",
          "creation_date": {
            "date": "2018-12-03 00:00:00.000000",
            "timezone_type": 3,
            "timezone": "Europe/Helsinki"
          },
          "last_donation_date": null
        },
        {
          ...
        }, ...
      ]
      ```
      
* ### /donate
  **Role:** Donate blood for a request. Can submit donation for self, or can specify another user id, to submit donation for someone else.

  **Request type:** POST

  **Request body:**
    * **token** - _mandatory_.
    * **user_id** - _optional_. If specified, the donation will be made by this user.
    * **donation_id** - _mandatory_.
    * **quantity** - _mandatory_. Quantity of blood to donate. Float.

  **Return on success**
    * {'**message**': 'You have donated successfully!'}
    
* ### /get_users/
  **Role:** Gets users from the database. User must be logged in and must be an "admin". Can specify "type" to select users by type (e.g.: **"type"**: **"doctor"**)

  **Request type:** POST

  **Request body:**
    * **token** - _mandatory_. The user token received when logging in.
    * **type** - _optional_. Type of the users to be extracted. One of **admin**, **center**, **doctor** or **donor**. String

  **Return on success**
    * JSON array of users, that looks like this:
      ```json
      [
        {
            "id": 1,
            "username": "student",
            "type": 3,
            "email": "mail@example.com",
            "blood_type": null,
            "hospital": "Floreasca",
            "is_valid": true,
            "last_donation_date": {
                "date": "2018-12-03 00:00:00.000000",
                "timezone_type": 3,
                "timezone": "Europe/Helsinki"
            }
        },
        {
          ...
        }, ...
      ]
      ```
            
