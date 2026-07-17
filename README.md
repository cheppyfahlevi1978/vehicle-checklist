# Hotel Fleet Check

A mobile-first digital vehicle checklist system for a 4-star hotel fleet
(SUV, Van, Buggy). Drivers, supervisors, and engineering staff track vehicle
condition, fuel level, and issues — with photos — before and after use.

## Stack

- Next.js (App Router) + TypeScript
- Tailwind CSS v4, shadcn/ui-style components, Lucide icons
- React Hook Form + Zod for validation
- Supabase (Auth, PostgreSQL, Storage) for backend

## Structure

- `/login` — email/password sign-in via Supabase Auth
- `/dashboard` — fleet overview grid with vehicle status badges
- `/checklist/[vehicleId]` — categorized checklist form (Exterior, Interior,
  Amenities, Engine), fuel level, and issue photo upload

## Database

Run the migration in `supabase/migrations/0001_init.sql` against your
Supabase project (via the SQL editor or `supabase db push`). It creates the
`users`, `vehicles`, `checklists`, and `checklist_items` tables, RLS
policies, a `checklist-photos` storage bucket, and sample fleet data.

## Running locally

```bash
npm install
cp .env.example .env.local # fill in your Supabase project URL + publishable key
npm run dev
```

Open [http://localhost:3000](http://localhost:3000).
