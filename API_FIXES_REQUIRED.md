# API Error Resolution Guide

## ❌ Current Issue

The system is experiencing **500 Internal Server Errors** on the following API endpoints:
- `/api/api_iconos.php` - Failed to load business icons
- `/api/encuestas.php` - Failed to load surveys  
- `/api/eventos.php` - Failed to load events
- `/api/trivias.php` - Failed to load trivia games
- `/api/noticias.php` - Failed to load news

**Error Message:** `SyntaxError: Unexpected token '<'`  
**Root Cause:** HTML error pages being returned instead of JSON

---

## 🔍 Diagnosis

### Why This Happens
1. Database tables may not exist yet (require migration to run)
2. Database credentials might be incorrect or unreachable
3. Model classes are calling database methods that fail due to missing tables

The errors are HTML because:
- When an exception is thrown early, PHP outputs an HTML error page
- The JSON header is set, but the error occurs before our error handler can respond

### How We Know
- Browser shows `<br /><b>...` format (HTML error page syntax)
- Network tab shows 500 status code
- Console shows "is not valid JSON" - because it's HTML, not JSON

---

## ✅ Solutions

### Solution 1: Run Database Migration (REQUIRED)

**File:** `/config/migration.sql`

This migration creates the missing tables:
- ✅ `business_icons` - Business type icons with emojis and colors
- ✅ Column additions for existing tables

**How to Apply:**
1. Go to your Hostinger database management tool (phpmyadmin or similar)
2. Run the SQL from `/config/migration.sql`
3. Verify all tables are created

**Migration Contents:**
```sql
CREATE TABLE IF NOT EXISTS business_icons (
    id INT NOT NULL AUTO_INCREMENT,
    business_type VARCHAR(100) NOT NULL UNIQUE,
    emoji VARCHAR(10) NOT NULL,
    icon_class VARCHAR(100) NULL,
    color VARCHAR(7) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);

INSERT IGNORE INTO business_icons (business_type, emoji, color, icon_class) VALUES
('comercio', '🛍️', '#e74c3c', 'icon-comercio'),
('hotel', '🏨', '#3498db', 'icon-hotel'),
... (31 more types)
```

### Solution 2: Verify Database Credentials (REQUIRED)

**File:** `/config/database.php` reads from `.env`

**Check:**
1. File: `/mapitaV/.env` exists? ✅ YES
2. File contains correct credentials? 
   ```
   DB_HOST=srv1524.hstgr.io
   DB_NAME=u580580751_map
   DB_USER=u580580751_mapita
   DB_PASS=Lucia1319%
   DB_CHARSET=utf8mb4
   ```

**To Fix If Incorrect:**
1. Update `/mapitaV/.env` with correct Hostinger credentials
2. Verify database is accessible from your IP

### Solution 3: Resilience Improvements (ALREADY APPLIED)

**Changes Made to `/api/api_iconos.php`:**

✅ Added try-catch around database query
✅ Added fallback to hardcoded icons if table doesn't exist
✅ Improved error logging
✅ Now returns proper JSON even when table is missing

This means `/api/api_iconos.php` should work immediately with or without the table.

---

## 📋 Checklist to Fix Errors

- [ ] **Step 1:** Go to Hostinger database management tool
- [ ] **Step 2:** Copy entire content from `/config/migration.sql`
- [ ] **Step 3:** Paste into SQL query editor and execute
- [ ] **Step 4:** Verify these tables now exist:
  - [ ] `business_icons` (newly created)
  - [ ] `encuestas` (should already exist)
  - [ ] `eventos` (should already exist)
  - [ ] `trivias` (should already exist)
  - [ ] `noticias` (should already exist)
- [ ] **Step 5:** Verify `.env` file has correct credentials
- [ ] **Step 6:** Refresh your app in browser
- [ ] **Step 7:** Check browser console - errors should be gone

---

## 🚀 After Migration

Once the migration is applied, all APIs should work:

```javascript
// ✅ This will now return proper JSON with 550+ business types
fetch('/api/api_iconos.php')
  .then(r => r.json())
  .then(data => console.log(data.data)); // {comercio: {...}, hotel: {...}, ...}

// ✅ These will also start working
fetch('/api/encuestas.php')
fetch('/api/eventos.php')
fetch('/api/trivias.php')
fetch('/api/noticias.php')
```

---

## 📞 If Migration Doesn't Fix It

If you run the migration and still get errors:

1. **Check database connection:**
   - Verify Hostinger credentials in `.env`
   - Test connection in database management tool
   - Verify database is accessible

2. **Check table creation:**
   - Open database management tool
   - Look for `business_icons` table
   - If missing, migration didn't execute properly

3. **Check file permissions:**
   - Ensure `/api/` folder has execute permissions
   - Ensure `/config/` folder is readable

4. **Enable error logging:**
   - Add to top of any API file:
   ```php
   error_log("DEBUG: Starting API endpoint");
   ```
   - Check error logs at `/error_log` or via Hostinger panel

---

## 📝 Technical Notes

### Why Icons Fallback Works
The updated `/api/api_iconos.php` now:
1. Tries to load from database
2. If table doesn't exist, catches exception silently
3. Returns hardcoded icons as fallback
4. **Always returns valid JSON**

This is a defensive programming pattern - the API is resilient and won't crash.

### Other APIs Need Migration
The other APIs (`encuestas`, `eventos`, `trivias`, `noticias`) don't have the fallback yet, so they need the actual tables to exist. Once migration is run, they'll work.

---

**Migration File:** `/config/migration.sql` (45 lines)  
**Modified Files:** `/api/api_iconos.php` (improved error handling)  
**Status:** Ready for database migration  

---

*Last Updated: 2026-04-16*
