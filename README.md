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
