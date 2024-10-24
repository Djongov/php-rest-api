# PHP Tailwind Dashbaord template

A template for quickly deploying apps (best suited for dashboard apps) that will run the most modern PHP version, has MySQL DB and authentication methods such as local, Microsoft Entra ID (Azure AD), Microsoft LIVE, Google out of the box and a lot of nice components such as DataGrid and Forms. Suitable for monolith or microservices. Has content-security-policy reporting endpoint, Firewall to filter traffic and much more. Ready for deployment anywhere, on Windows IIS, Apache, Nginx and Docker containers. Supports out of the box color dark/light and color themes as well as user and admin panels. All written in pure PHP. Good for learning too.

## Dependencies

Dependencies you can see in the `composer.json` file. Make sure to run `composer update` on fresh pulls.

Create a directory called `profile` in /public/assets/images

## To Do

- Find a way to secure /login?destination=https://bad-domain.com. Logic to rewrite the "state" paramter is there but JS does something to prevent it from functioning well

## Routing and available variables

Routing provides the following variables for each controller

**GET** requests

``$usernameArray`` - An array holding user info

``$isAdmin`` - A boolean if the user is an admin

**POST**, **PUT**, **DELETE** and other REST methods

``$vars`` - An array holding 4 keys

**usernameArray** (An array holding user info)

**isAdmin** (A boolean if the user is an admin)

**loggedIn** (A boolean if the user is an admin)

**theme** (a string holind the user theme)

## DataGrid

max_input_vars needs to be account for

