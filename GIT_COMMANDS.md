# Quick Start - Git Commands

Run these commands to push the project to GitHub and trigger the Judge:

```powershell
# Navigate to project directory
cd c:\Users\user\Desktop\moviesuggestor

# Initialize git repository
git init

# Add all files
git add .

# Create initial commit
git commit -m "Phase 1: Minimal working movie suggester implementation

- Added database schema with 15 sample movies
- Implemented category and score filtering
- Created responsive web interface
- Added comprehensive PHPUnit tests
- Configured Judge workflow for CI/CD
- All Phase 1 requirements complete"

# Set main branch
git branch -M main

# Add remote (replace with your actual repo URL if different)
git remote add origin https://github.com/TheoSfak/moviesuggestor.git

# Push to GitHub
git push -u origin main
```

## After Pushing

1. Go to https://github.com/TheoSfak/moviesuggestor/actions
2. Watch the Judge workflow run
3. Wait for GREEN checkmark ✅
4. If RED ❌, check the logs and fix issues
5. Only proceed to Phase 2 after Judge approval

## Common Issues

### If repository doesn't exist on GitHub yet:
```powershell
# Create it via GitHub CLI (if installed)
gh repo create moviesuggestor --public --source=. --remote=origin --push

# Or create manually at https://github.com/new
```

### If you get authentication errors:
```powershell
# Configure git with your credentials
git config --global user.name "Your Name"
git config --global user.email "your.email@example.com"

# You may need to setup a Personal Access Token
# Go to: GitHub → Settings → Developer settings → Personal access tokens
```

### If you need to force push (use with caution):
```powershell
git push -f origin main
```
