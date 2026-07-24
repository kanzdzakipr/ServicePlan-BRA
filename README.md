# Equipment Maintenance & Fleet Monitoring System

This is the Equipment Maintenance & Fleet Monitoring System built with Next.js, Tailwind CSS, shadcn/ui, Prisma, and MySQL.

## Getting Started

### Prerequisites
- Node.js (v18+)
- npm
- Docker & Docker Compose (for MySQL development)

### Setup

1. **Start the database:**
   Run the following command to start the MySQL database container:
   ```bash
   docker-compose up -d
   ```

2. **Environment Variables:**
   Copy the `.env.example` file to `.env` and configure your database URL if different from the default:
   ```bash
   cp .env.example .env
   ```

3. **Install Dependencies:**
   Install project dependencies using npm:
   ```bash
   npm install
   ```

4. **Initialize Database:**
   Push the Prisma schema to your database to create tables:
   ```bash
   npx prisma db push
   ```

5. **Start the Development Server:**
   Run the Next.js development server:
   ```bash
   npm run dev
   ```

Open [http://localhost:3000](http://localhost:3000) with your browser to see the result.

## Folder Structure (Feature-Based)
- `src/app`: Contains all Next.js App Router pages and global layouts.
- `src/components`: Reusable UI components (including shadcn/ui components).
- `src/features`: Feature-based logic and components (e.g., `assets`, `work-orders`, `monitoring`).
- `src/lib`: Utility functions and Prisma client setup.
