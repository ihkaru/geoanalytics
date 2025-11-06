# GeoAnalytics Project

## Project Overview

This is a web application for geoanalytics, built with a multi-container architecture using Docker Compose.

- **Frontend:** A Vue.js 3 Single-Page Application (SPA) using [Framework7](https://framework7.io/) for UI components, [Pinia](https://pinia.vuejs.org/) for state management, and [Vite](https://vitejs.dev/) for the development and build tooling.
- **Backend:** A PHP/Laravel API that serves data to the frontend.
- **Database:** PostgreSQL with the PostGIS extension for storing and querying geospatial data.
- **Web Server:** Nginx is used as a reverse proxy to route requests to the appropriate service.

## Building and Running

The entire application stack is managed by Docker Compose.

**To start all services:**

```sh
docker-compose up -d
```

- The main application will be accessible at [http://localhost:8000](http://localhost:8000).
- The frontend Vite development server runs on [http://localhost:5173](http://localhost:5173).
- The PostgreSQL database is exposed on port `5433` on the host machine.

**To stop all services:**

```sh
docker-compose down
```

## Development Conventions

### Frontend

The frontend code is located in the `frontend/` directory.

- **Code Style:** Code formatting is enforced by [Prettier](https://prettier.io/). To format the code, run:
  ```sh
  npm run format
  ```
- **Linting:** Code quality is checked with [ESLint](https://eslint.org/). To run the linter, run:
  ```sh
  npm run lint
  ```
- **Dependencies:** Frontend dependencies are managed with `npm`. Install them with `npm install` inside the `frontend` directory.

### Backend

The backend code is located in the `backend/` directory and is a standard Laravel application.

- Follow standard Laravel coding conventions and best practices.
- Backend dependencies are managed with Composer.
