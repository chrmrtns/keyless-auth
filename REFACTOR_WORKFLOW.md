# Refactoring Workflow for v3.3.0

This directory is a **Git worktree** for refactoring Core.php into modular classes.

## Refactoring Progress

### Phase 7: REST API Implementation ✅ COMPLETED
**Branch:** feature/refactor-core-v3.3.0
**Status:** Completed
**Date:** 2025-11-11

#### What Was Implemented:
1. **REST API Controller** (`includes/API/RestController.php`)
   - New endpoint: `POST /wp-json/keyless-auth/v1/request-login`
   - Accepts `email_or_username` and optional `redirect_url` parameters
   - Returns proper HTTP status codes (200, 404, 403, 500)
   - Includes filter hook: `chrmrtns_kla_rest_api_enabled`

2. **JavaScript API Abstraction Layer** (`assets/js/keyless-auth-api.js`)
   - `KeylessAuthAPI` class that auto-switches between REST and AJAX
   - Unified response format for both methods
   - Graceful degradation and error handling

3. **Feature Flag System**
   - Added "Enable REST API (Beta)" checkbox in Options page
   - Option: `chrmrtns_kla_enable_rest_api` (defaults to disabled)
   - Allows programmatic control via filter hook

4. **Frontend Integration**
   - Updated `includes/Frontend/AssetLoader.php` with `enqueueFrontendScripts()` method
   - Modified WooCommerce integration to use API abstraction layer
   - Maintains backward compatibility with existing AJAX handlers

5. **Documentation**
   - Added comprehensive REST API section to README.md
   - Created new "🔌 REST API" tab in Help page
   - Includes JavaScript, PHP, and cURL examples
   - Documents all endpoints, parameters, and response codes

6. **Testing**
   - Created standalone test page: `test-rest-api.html`
   - Works from any location (no WordPress context needed)
   - Tests both REST and AJAX endpoints
   - Includes debug information panel

#### Files Created:
- `includes/API/RestController.php` (228 lines)
- `includes/API/index.php` (security file)
- `assets/js/keyless-auth-api.js` (165 lines)
- `test-rest-api.html` (241 lines)

#### Files Modified:
- `includes/Core/Core.php` - Added RestController initialization
- `includes/Admin/Settings/SettingsManager.php` - Registered REST API option
- `includes/Admin/Pages/OptionsPage.php` - Added REST API checkbox
- `includes/Frontend/AssetLoader.php` - Added enqueueFrontendScripts() method
- `includes/Core/WooCommerce.php` - Updated to use API layer
- `assets/js/woocommerce-integration.js` - Integrated API abstraction layer
- `includes/Admin/Pages/HelpPage.php` - Added REST API tab
- `assets/css/help-page.css` - Added REST API tab selectors
- `README.md` - Added REST API documentation

#### Technical Decisions:
- REST API runs in **parallel** with AJAX (not replacement) for backward compatibility
- Basic endpoints are **free** (Pro features planned: bulk operations, webhooks, rate limiting, analytics, OAuth 2.0)
- Uses WordPress REST nonce verification (`wp_rest`)
- Returns `WP_Error` objects with proper HTTP status codes
- Follows WordPress REST API conventions

#### Testing Results:
✅ REST endpoint works without authentication (via `rest_authentication_errors` filter whitelist)
✅ Proper error handling (404, 403, 500, 503)
✅ Success response with magic link email sent
✅ WooCommerce integration working with API layer
✅ Backward compatibility maintained with AJAX

---

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
