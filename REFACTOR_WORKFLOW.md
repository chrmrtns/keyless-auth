# Refactoring Workflow for v3.3.0

This directory is a **Git worktree** for refactoring Core.php into modular classes.

## Directory Structure

```
keyless-auth/           (main branch)    - For v3.2.x patches
keyless-auth-refactor/  (feature branch) - For v3.3.0 refactoring work
keyless-auth-svn/       (SVN)            - WordPress.org releases
```

## Workflow

### Working on v3.3.0 Refactoring

```bash
cd /Users/christianmartens/Documents/GitHub/keyless-auth-refactor
# Make changes, commit normally
git add .
git commit -m "Refactor: Extract LoginRedirect class from Core.php"
git push
```

### If a v3.2.x Hotfix is Needed

```bash
# 1. Switch to main directory
cd /Users/christianmartens/Documents/GitHub/keyless-auth

# 2. Make the fix
# Edit files...
git add .
git commit -m "Fix: Critical bug in feature X"

# 3. Update version to v3.2.3 in keyless-auth.php, readme.txt, README.md
# 4. Push to GitHub
git push

# 5. Release to WordPress.org (same process as v3.2.2)

# 6. Merge the hotfix into refactor branch
cd /Users/christianmartens/Documents/GitHub/keyless-auth-refactor
git merge main
# Resolve any conflicts if necessary
git push
```

### When Refactoring is Complete

```bash
# 1. Ensure feature branch is up to date
cd /Users/christianmartens/Documents/GitHub/keyless-auth-refactor
git merge main  # Bring in any v3.2.x patches

# 2. Update version to 3.3.0
# Edit keyless-auth.php, readme.txt, README.md

# 3. Switch to main and merge
cd /Users/christianmartens/Documents/GitHub/keyless-auth
git checkout main
git merge feature/refactor-core-v3.3.0

# 4. Push and release as v3.3.0
git push
# Follow normal release process
```

## Useful Commands

**List all worktrees:**
```bash
git worktree list
```

**Check current branch:**
```bash
git branch --show-current
```

**Remove worktree when done:**
```bash
git worktree remove keyless-auth-refactor
```

## GitHub

- **Main branch**: https://github.com/chrmrtns/keyless-auth/tree/main
- **Feature branch**: https://github.com/chrmrtns/keyless-auth/tree/feature/refactor-core-v3.3.0
- **Issue**: https://github.com/chrmrtns/keyless-auth/issues/1

## Notes

- Both directories share the same `.git` repository
- Commits in either directory are tracked in the same Git history
- You can work on both simultaneously without conflicts
- The feature branch is backed up on GitHub
