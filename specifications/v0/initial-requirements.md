## Introduction

I want to build an application that helps me plan and track my training.
The goal of this application is to gamify training using RPG mechanism of experience and levels.
This application will be use Symfony 8 and php 8.4.
It will provide: 
 - an admin from which reference data can be managed (for example Equipment or Movement)
 - a website usable by the User to create, plan and track there training session and by the Coach to create, plan and track there client training session



## Technical information

- We will rely on Docker for development. We will need a database container for MySQL.
- A rest API will be created, the authentication will be managed using JWT.
- The admin will be built using **React + TypeScript + Ant Design**. We deliberately do NOT use react-admin: the app stays a regular React TS project (Vite + react-router + TanStack Query + antd's components), which gives full control over UX and lets us reuse the same building blocks across the player website later. Components must be **small, focused, and reusable** — no monolithic multi-task pages. The admin **must support a light / dark theme toggle** persisted across sessions.
- The website will be built using React TypeScript.
- Rely on the coding conventions defined in the `specifications/conventions.md` to create all PHP/Symfony code.

## Theming
The design of the website will be inspired by D&D as the application will have a medieval fantasy feeling.
Create css variable to manage colors.

## Description of the functionalities

### Admin
An admin as the ability to:
- Log in the Admin website
- create / update / delete Equipment (describes the usable equipment like barbell, dumbbell etc)
- create / update / delete Muscle, here is a base list that could be generated with fixtures:
  - biceps
  - triceps
  - quadriceps
  - calves
  - chest
  - glutes
  - hamstrings
  - abdominal
  - abductors
  - adductors
  - forearms
  - lower-back
  - cardio
  - full-body
  - neck
  - shoulders
  - lats
  - traps
  - upper-back
  - other
- create / update / delete Movement (describes the movement to execute in the workout like biceps curl, bench press close grip, running)
  - A movement is linked to one or many equipments
  - A movement is linked to a main muscle and a list of secondary muscles
  - A movement defines the type of data he requires to be tracked:
    - does he require to track repetitions, weight, duration, distance, incline in percent, incline in meters

### Player (a non-Coach user)
- A Player as the ability to register
- A Player as the ability to log in the Player website
- Create a new empty Workout
  - Two possibilities:
    - Start empty workout (this will set the dateStart of the Workout, and it's status to in-progress)
    - Plan a new workout (this will require the Player to set a date to execute that workout)
  - Add Movement to it, and for each added movement to:
    - The rest duration in between ExerciseSet
    - define multiple ExerciseSet, for each he can define the target for 
      - the number of repetitions if the movement call for it
      - the weight if the movement call for it
      - the duration if the movement call for it
      - the distance if the movement call for it
  - If the Workout is in-progress, the Player can define for each of the previous property what he actually achieved compare to what was planned.
  - He can finish a Workout:
    - if all ExerciseSet for all Movement have been completed, the endDate is set and the status is changed to completed 
    - if not, a model opens letting him know that the workout cannot be completed as some Exercise aren't complete
  - On termination of a Workout, all ExerciseSet are check to see if the Player as achieve any new personal best:
    - Highest weight for a given movement
    - Highest reps for a given movement
    - Highest volume for one set (reps * weight) for a given movement
    - Highest volume for a movement in a workout (reps * weight added for each set)
    - Highest duration for a given movement
    - Highest distance for a given movement
    - Highest speed (duration * distance) for a given movement
- A Player as the ability to see its training history (list of all previous workout) and review there details
- A player as the ability to see its future training sessions (list of all incoming workout)
- A player as the ability to see all of it's best result per movement (a list of all movement with all achievements)
- A player as the ability to log out
