# General Guidelines

- Provide short step by step instructions and DO NOT expand too much unless asked specifically.
- When providing code examples:
  - Focus on architecture and patterns rather than full implementations, unless asked.

# Git repo related instructions

- Use conventional commit message format always using prefixes in commit messages.
- Always use the imperative mood in the subject line. Do not capitalize the first letter of the subject line.
- Do not end the subject line with a period.Reference issues and pull requests liberally.
- Always use the standard labels for git commit messages.
- No description in the commit message body.
- DO NOT add explanatory text below the first line. Keep the git commit messages in one line.
- Keep git commit messages concise.

# Project Technical Details & Structure

- The project will have 2 main functions. One will be the api and the second one will be the frontend.
- For all libraries and tools used consider the latest versions and consult the latest documentation available for them.

## API specific instructions

- The api will use PHP and especially it will utilize the SLIM framework along with the MEDOO.
- PHP Library https://github.com/tiagohillebrandt/aerofetch will be used to get aircraft and airport data.

## Frontend specific instructions

- The frontend application will use the latest VUE framework for the UI. Use all the best practices from VUE. Use Composition API and VUE 3.
- Use Pinia for state management and official vuew router.
- Use official VUE documentation for all the VUE features.
- Use the latest Quasar framework.
- DO NOT use typescript.

# Project Description

- The application is a personal Flight Simulator tour viewer. Flights are part of a tour and for each flight certain information is kept.
- The main page of the application is a world map. The map shows one or more selected tours. Each tour has a certain color on the map.
- Each leg has information assigned to it (tour-id,origin,destination,route,aircraft type,link,comments).
- Form for the user to enter a new tour (tourid, tour-description) (protected by password).
- Form for the user to enter a new leg (protected by password).
- There will be an option the user to delete or edit the leg data (protected by password).
