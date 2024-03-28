#!/bin/bash

# Ensure we're on the develop branch
git checkout develop

# Pull the latest changes
git pull origin develop

# Switch to the main branch
git checkout main

# Pull the latest changes on main too
git pull origin main

# Merge develop into main
git merge develop

# Push the changes to main
git push origin main

# Switch back to the develop branch
git checkout develop
